<?php

namespace Tests\Feature\Api;

use App\Contracts\SmsGatewayContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The replacement for phone+OTP-only login: create an account with name,
 * phone and password (phone verified once via OTP), then log in afterwards
 * with just phone + password. An account from before this existed — or one
 * whose signup was never finished — has no usable password yet, and reaches
 * one the same way a forgotten password would be reset.
 */
class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> phone => plaintext code, most recent wins */
    public array $sentCodes = [];

    protected function setUp(): void
    {
        parent::setUp();

        // OtpService hashes the code before persisting it, so the only way
        // to recover the plaintext in a test is to capture it where it's
        // still plaintext: the outgoing SMS. Swap the dev-log gateway for a
        // fake that remembers it instead.
        $this->sentCodes = [];
        $this->app->bind(SmsGatewayContract::class, function () {
            return new class($this) implements SmsGatewayContract
            {
                public function __construct(private CustomerAuthTest $test) {}

                public function send(string $phone, string $message): bool
                {
                    preg_match('/\d{6}/', $message, $m);
                    $this->test->sentCodes[$phone] = $m[0] ?? '';

                    return true;
                }
            };
        });
    }

    public function test_registering_sends_an_otp_and_verifying_it_activates_the_account(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'حسين علي', 'phone' => '07701234567', 'password' => 'correct-horse',
        ])->assertStatus(200);

        $pending = User::where('phone', '+9647701234567')->first();
        $this->assertNotNull($pending);
        $this->assertNull($pending->phone_verified_at);
        $this->assertTrue(Hash::check('correct-horse', $pending->password));

        $response = $this->postJson('/api/v1/auth/register/verify', [
            'phone' => '7701234567', 'code' => $this->sentCodes['+9647701234567'],
        ])->assertOk();

        $response->assertJsonPath('user.name', 'حسين علي')->assertJsonStructure(['token']);
        $this->assertNotNull($pending->fresh()->phone_verified_at);
    }

    public function test_registering_an_already_verified_phone_is_rejected(): void
    {
        User::factory()->create(['phone' => '+9647701234567']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'أحمد', 'phone' => '07701234567', 'password' => 'correct-horse',
        ])->assertStatus(422)->assertJsonPath('error.code', 'ALREADY_REGISTERED');
    }

    public function test_registering_the_same_unverified_phone_again_updates_the_pending_signup(): void
    {
        $this->postJson('/api/v1/auth/register', ['name' => 'اسم أول', 'phone' => '07701234567', 'password' => 'first-password'])
            ->assertOk();

        // Past the OTP resend cooldown, so the second attempt isn't blocked
        // by that — this test is about the pending row being updated, not resend timing.
        $this->travel(61)->seconds();

        $this->postJson('/api/v1/auth/register', ['name' => 'اسم ثاني', 'phone' => '07701234567', 'password' => 'second-password'])
            ->assertOk();

        $this->assertDatabaseCount('users', 1);
        $user = User::where('phone', '+9647701234567')->first();
        $this->assertSame('اسم ثاني', $user->name);
        $this->assertTrue(Hash::check('second-password', $user->password));
    }

    public function test_an_unverified_registration_cannot_be_confirmed_with_the_wrong_code(): void
    {
        $this->postJson('/api/v1/auth/register', ['name' => 'اسم', 'phone' => '07701234567', 'password' => 'correct-horse'])
            ->assertOk();

        $this->postJson('/api/v1/auth/register/verify', ['phone' => '07701234567', 'code' => '000000'])
            ->assertStatus(422)->assertJsonPath('error.code', 'OTP_INVALID');

        $this->assertNull(User::where('phone', '+9647701234567')->first()->phone_verified_at);
    }

    public function test_login_succeeds_with_the_right_password_and_fails_with_the_wrong_one(): void
    {
        $user = User::factory()->create(['phone' => '+9647701234567', 'password' => 'correct-horse']);

        $this->postJson('/api/v1/auth/login', ['phone' => '07701234567', 'password' => 'correct-horse'])
            ->assertOk()->assertJsonPath('user.phone', $user->phone)->assertJsonStructure(['token']);

        $this->postJson('/api/v1/auth/login', ['phone' => '07701234567', 'password' => 'wrong-password'])
            ->assertStatus(422)->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_login_fails_for_an_unknown_phone_with_the_same_generic_message(): void
    {
        $response = $this->postJson('/api/v1/auth/login', ['phone' => '07799999999', 'password' => 'whatever1']);

        $response->assertStatus(422)->assertJsonPath('error.code', 'INVALID_CREDENTIALS')
            ->assertJsonPath('error.message', 'رقم الهاتف أو كلمة المرور غير صحيحة.');
    }

    public function test_login_fails_for_an_account_that_never_set_a_password(): void
    {
        // A legacy phone-only account: verified, but password is still null.
        User::factory()->create(['phone' => '+9647701234567', 'password' => null]);

        $this->postJson('/api/v1/auth/login', ['phone' => '07701234567', 'password' => 'anything1'])
            ->assertStatus(422)->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_login_fails_for_an_unverified_pending_registration(): void
    {
        $this->postJson('/api/v1/auth/register', ['name' => 'قيد التحقق', 'phone' => '07701234567', 'password' => 'correct-horse'])
            ->assertOk();

        $this->postJson('/api/v1/auth/login', ['phone' => '07701234567', 'password' => 'correct-horse'])
            ->assertStatus(422)->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_a_legacy_account_sets_its_first_password_via_otp_then_can_log_in(): void
    {
        $user = User::factory()->create(['phone' => '+9647701234567', 'password' => null]);

        $this->postJson('/api/v1/auth/password/request-otp', ['phone' => '07701234567'])->assertOk();

        $this->postJson('/api/v1/auth/password/set', [
            'phone' => '07701234567', 'code' => $this->sentCodes['+9647701234567'], 'password' => 'new-password',
        ])->assertOk()->assertJsonStructure(['token']);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));

        $this->postJson('/api/v1/auth/login', ['phone' => '07701234567', 'password' => 'new-password'])
            ->assertOk();
    }

    public function test_forgot_password_resets_an_existing_password_via_otp(): void
    {
        $user = User::factory()->create(['phone' => '+9647701234567', 'password' => 'old-password']);

        $this->postJson('/api/v1/auth/password/request-otp', ['phone' => '07701234567'])->assertOk();

        $this->postJson('/api/v1/auth/password/set', [
            'phone' => '07701234567', 'code' => $this->sentCodes['+9647701234567'], 'password' => 'brand-new-password',
        ])->assertOk();

        $fresh = $user->fresh();
        $this->assertFalse(Hash::check('old-password', $fresh->password));
        $this->assertTrue(Hash::check('brand-new-password', $fresh->password));
    }

    public function test_a_password_reset_otp_cannot_be_replayed(): void
    {
        $user = User::factory()->create(['phone' => '+9647701234567', 'password' => 'old-password']);
        $this->postJson('/api/v1/auth/password/request-otp', ['phone' => '07701234567'])->assertOk();
        $code = $this->sentCodes['+9647701234567'];

        $this->postJson('/api/v1/auth/password/set', ['phone' => '07701234567', 'code' => $code, 'password' => 'first-new'])
            ->assertOk();

        $this->postJson('/api/v1/auth/password/set', ['phone' => '07701234567', 'code' => $code, 'password' => 'second-new'])
            ->assertStatus(422)->assertJsonPath('error.code', 'OTP_INVALID');

        $this->assertTrue(Hash::check('first-new', $user->fresh()->password));
    }
}
