<?php

namespace Tests\Feature\Api;

use App\Contracts\SmsGatewayContract;
use App\Models\Admin;
use App\Models\AuthLockout;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\LoginLockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Escalating lockout for wrong passwords / codes (1 h, then 10 h, 24 h, ...) and the caps
 * on how many one-time codes can be sent. The per-minute throttle middleware is set aside
 * here so the rules under test are the ones being exercised.
 */
class LockoutAndOtpCapsTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+9647701234567';

    /** @var array<string, string> */
    public array $sentCodes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->sentCodes = [];
        $this->app->bind(SmsGatewayContract::class, function () {
            return new class($this) implements SmsGatewayContract
            {
                public function __construct(private LockoutAndOtpCapsTest $test) {}

                public function send(string $phone, string $message): bool
                {
                    preg_match('/\d{6}/', $message, $m);
                    $this->test->sentCodes[$phone] = $m[0] ?? '';

                    return true;
                }
            };
        });
    }

    private function customer(string $password = 'correct-horse'): User
    {
        return User::factory()->create([
            'phone' => self::PHONE, 'password' => Hash::make($password), 'phone_verified_at' => now(), 'status' => 'active',
        ]);
    }

    private function login(string $password, string $phone = self::PHONE)
    {
        return $this->postJson('/api/v1/auth/login', ['phone' => $phone, 'password' => $password]);
    }

    private function wrong(int $times, string $phone = self::PHONE): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->login('wrong-password', $phone);
        }
    }

    // ---- Customer password ----------------------------------------------------------

    public function test_three_wrong_passwords_lock_the_account_for_an_hour(): void
    {
        $this->customer();

        $this->login('nope')->assertStatus(422)->assertJsonPath('error.code', 'INVALID_CREDENTIALS')->assertJsonPath('error.attempts_left', 2);
        $this->login('nope')->assertStatus(422)->assertJsonPath('error.attempts_left', 1);

        $third = $this->login('nope');
        $third->assertStatus(429)->assertJsonPath('error.code', 'AUTH_LOCKED');
        $this->assertEqualsWithDelta(3600, $third->json('error.retry_after'), 5);
        $third->assertHeader('Retry-After');
        $this->assertStringContainsString('ساعة', $third->json('error.message'));
    }

    public function test_the_right_password_is_refused_while_locked_and_accepted_after_the_hour(): void
    {
        $this->customer();
        $this->wrong(3);

        $this->login('correct-horse')->assertStatus(429)->assertJsonPath('error.code', 'AUTH_LOCKED');

        $this->travel(59)->minutes();
        $this->login('correct-horse')->assertStatus(429);

        $this->travel(2)->minutes();
        $this->login('correct-horse')->assertOk()->assertJsonStructure(['token']);
    }

    public function test_every_lockout_is_longer_than_the_one_before(): void
    {
        $this->customer();
        $hours = fn (int $n) => $n * 3600;

        // 1st lockout: 1 hour.
        $this->wrong(2);
        $r = $this->login('nope');
        $this->assertEqualsWithDelta($hours(1), $r->json('error.retry_after'), 5);

        // After it ends, three more wrong tries: 10 hours.
        $this->travel(61)->minutes();
        $this->wrong(2);
        $r = $this->login('nope');
        $this->assertEqualsWithDelta($hours(10), $r->json('error.retry_after'), 5);

        // Then 24 hours.
        $this->travel(10)->hours();
        $this->travel(2)->minutes();
        $this->wrong(2);
        $r = $this->login('nope');
        $this->assertEqualsWithDelta($hours(24), $r->json('error.retry_after'), 5);

        // Then 3 days, and then 7 days for good.
        $this->travel(24)->hours();
        $this->travel(2)->minutes();
        $this->wrong(2);
        $this->assertEqualsWithDelta($hours(72), $this->login('nope')->json('error.retry_after'), 5);

        $this->travel(72)->hours();
        $this->travel(2)->minutes();
        $this->wrong(2);
        $this->assertEqualsWithDelta($hours(168), $this->login('nope')->json('error.retry_after'), 5);

        $this->travel(168)->hours();
        $this->travel(2)->minutes();
        $this->wrong(2);
        $this->assertEqualsWithDelta($hours(168), $this->login('nope')->json('error.retry_after'), 5, 'The longest lockout stays the longest.');
    }

    public function test_an_unknown_number_is_counted_and_answered_exactly_like_a_real_one(): void
    {
        $this->login('x', '+9647709999999')->assertStatus(422)->assertJsonPath('error.attempts_left', 2);
        $this->login('x', '+9647709999999')->assertStatus(422)->assertJsonPath('error.attempts_left', 1);
        $this->login('x', '+9647709999999')->assertStatus(429)->assertJsonPath('error.code', 'AUTH_LOCKED');
    }

    public function test_a_correct_password_clears_the_count(): void
    {
        $this->customer();

        $this->wrong(2);
        $this->login('correct-horse')->assertOk();
        $this->wrong(2);
        $this->login('nope')->assertStatus(429); // the third of the new streak locks

        $this->assertSame(1, AuthLockout::where('scope', 'customer')->count());
    }

    public function test_a_quiet_day_starts_the_count_over(): void
    {
        $this->customer();

        $this->wrong(2);
        $this->travel(25)->hours();
        $this->login('nope')->assertStatus(422)->assertJsonPath('error.attempts_left', 2);
    }

    public function test_one_account_locked_does_not_lock_another(): void
    {
        $this->customer();
        User::factory()->create(['phone' => '+9647712223333', 'password' => Hash::make('other-pass-1'), 'phone_verified_at' => now(), 'status' => 'active']);

        $this->wrong(3);

        $this->login('other-pass-1', '+9647712223333')->assertOk();
    }

    // ---- Wrong one-time codes ----------------------------------------------------------

    private function resetCode(string $code, string $phone = self::PHONE): void
    {
        OtpCode::create([
            'phone' => $phone, 'code_hash' => Hash::make($code), 'purpose' => 'password_reset',
            'max_attempts' => 5, 'expires_at' => now()->addMinutes(5),
        ]);
    }

    public function test_three_wrong_codes_lock_code_entry_and_even_the_right_code_waits(): void
    {
        $this->resetCode('123456');
        $payload = fn (string $code) => ['phone' => self::PHONE, 'code' => $code, 'password' => 'new-password-1'];

        $this->postJson('/api/v1/auth/password/set', $payload('000001'))->assertStatus(422)->assertJsonPath('error.code', 'OTP_INVALID');
        $this->postJson('/api/v1/auth/password/set', $payload('000002'))->assertStatus(422);
        $this->postJson('/api/v1/auth/password/set', $payload('000003'))->assertStatus(429)->assertJsonPath('error.code', 'AUTH_LOCKED');

        $this->postJson('/api/v1/auth/password/set', $payload('123456'))->assertStatus(429);

        // An hour later the lock is over; the old code has expired by then, so a fresh one is sent.
        $this->travel(61)->minutes();
        $this->resetCode('246810');
        $this->postJson('/api/v1/auth/password/set', $payload('246810'))->assertOk();
    }

    public function test_resetting_the_password_with_a_code_lifts_the_password_lock(): void
    {
        $this->customer();
        $this->wrong(3);
        $this->login('correct-horse')->assertStatus(429);

        $this->resetCode('654321');
        $this->postJson('/api/v1/auth/password/set', ['phone' => self::PHONE, 'code' => '654321', 'password' => 'brand-new-pass'])->assertOk();

        $this->login('brand-new-pass')->assertOk();
    }

    // ---- Dashboard sign-in -------------------------------------------------------------

    public function test_the_dashboard_login_locks_after_three_wrong_passwords_and_escalates(): void
    {
        $admin = Admin::create([
            'name' => 'Test Admin', 'email' => 'lock@qreach.app', 'password' => 'CorrectPassword123!',
            'status' => 'active', 'must_change_password' => false,
        ]);

        foreach (range(1, 3) as $i) {
            $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong']);
        }

        $this->assertTrue($admin->fresh()->isLocked());

        $response = $this->post('/admin/login', ['email' => $admin->email, 'password' => 'CorrectPassword123!']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('ساعة', session('errors')->first('email'));
        $this->assertGuest('admin');

        $this->travel(61)->minutes();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'CorrectPassword123!'])->assertRedirect();
        $this->assertAuthenticated('admin');
    }

    public function test_the_lockout_service_gives_the_documented_durations(): void
    {
        $lockout = app(LoginLockout::class);
        $seen = [];

        foreach (range(1, 6) as $round) {
            foreach (range(1, 3) as $i) {
                $state = $lockout->recordFailure('customer', 'x@example.com');
            }
            $seen[] = $state['retry_after'];
            $this->travelTo(now()->addSeconds($state['retry_after'] + 1));
        }

        $this->assertEqualsWithDelta([3600, 36000, 86400, 259200, 604800, 604800], $seen, 3);
    }

    public function test_old_quiet_rows_are_pruned(): void
    {
        AuthLockout::create(['scope' => 'customer', 'identifier' => 'old', 'failures' => 1, 'last_failure_at' => now()->subDays(40)]);
        AuthLockout::create(['scope' => 'customer', 'identifier' => 'locked', 'failures' => 0, 'level' => 1, 'locked_until' => now()->addHour(), 'last_failure_at' => now()]);

        $this->assertSame(1, app(LoginLockout::class)->prune());
        $this->assertSame(['locked'], AuthLockout::pluck('identifier')->all());
    }

    // ---- Sending one-time codes --------------------------------------------------------

    private function sentTo(string $phone, int $minutesAgo, ?string $ip = null): void
    {
        $otp = OtpCode::create([
            'phone' => $phone, 'code_hash' => Hash::make('111111'), 'purpose' => 'password_reset',
            'max_attempts' => 5, 'expires_at' => now()->addMinutes(5), 'request_ip' => $ip,
        ]);
        $otp->forceFill(['created_at' => now()->subMinutes($minutesAgo)])->save();
    }

    private function ask(string $phone = self::PHONE)
    {
        return $this->postJson('/api/v1/auth/password/request-otp', ['phone' => $phone]);
    }

    public function test_a_number_gets_at_most_five_codes_an_hour(): void
    {
        foreach ([55, 45, 35, 25, 15] as $minutes) {
            $this->sentTo(self::PHONE, $minutes);
        }

        $response = $this->ask();
        $response->assertStatus(429)->assertJsonPath('error.code', 'OTP_LIMIT');
        // Free again when the oldest of the five (55 min ago) leaves the hour: about 5 minutes.
        $this->assertEqualsWithDelta(300, $response->json('error.retry_after'), 10);

        $this->travel(6)->minutes();
        $this->ask()->assertOk();
    }

    public function test_a_number_gets_at_most_ten_codes_a_day(): void
    {
        foreach (range(1, 10) as $i) {
            $this->sentTo(self::PHONE, 120 + $i * 30); // spread over the last ~7 hours, none in the last hour
        }

        $this->ask()->assertStatus(429)->assertJsonPath('error.code', 'OTP_LIMIT');
    }

    public function test_each_resend_waits_longer_than_the_last(): void
    {
        // One code a while ago: the base 60 seconds.
        $this->sentTo(self::PHONE, 10);
        $this->ask()->assertOk();

        // Two codes in the hour now: the next wait is 120 seconds (the one just sent counts).
        $this->ask()->assertStatus(429)->assertJsonPath('error.code', 'OTP_COOLDOWN');
        $this->travel(61)->seconds();
        $this->ask()->assertStatus(429)->assertJsonPath('error.code', 'OTP_COOLDOWN');
        $this->travel(61)->seconds();
        $this->ask()->assertOk();
    }

    public function test_the_phone_written_differently_is_the_same_number_for_the_cap(): void
    {
        foreach ([55, 45, 35, 25, 15] as $minutes) {
            $this->sentTo(self::PHONE, $minutes);
        }

        $this->ask('07701234567')->assertStatus(429)->assertJsonPath('error.code', 'OTP_LIMIT');
        $this->ask('7701234567')->assertStatus(429)->assertJsonPath('error.code', 'OTP_LIMIT');
    }

    public function test_one_address_cannot_ask_for_codes_for_endless_numbers(): void
    {
        foreach (range(1, 15) as $i) {
            $this->sentTo('+96477000000'.sprintf('%02d', $i), 30, '127.0.0.1');
        }

        $this->ask('+9647712340000')->assertStatus(429)->assertJsonPath('error.code', 'OTP_LIMIT');
    }

    public function test_there_is_a_daily_cap_for_the_whole_system(): void
    {
        config(['security.otp.global_day' => 4]);
        foreach (range(1, 4) as $i) {
            $this->sentTo('+96477100000'.sprintf('%02d', $i), 30, '10.0.0.'.$i);
        }

        $this->ask('+9647799990000')->assertStatus(429)->assertJsonPath('error.code', 'OTP_LIMIT');
    }

    public function test_register_is_capped_the_same_way(): void
    {
        foreach ([55, 45, 35, 25, 15] as $minutes) {
            $this->sentTo(self::PHONE, $minutes);
        }

        $this->postJson('/api/v1/auth/register', ['name' => 'سارة', 'phone' => '07701234567', 'password' => 'correct-horse'])
            ->assertStatus(429)->assertJsonPath('error.code', 'OTP_LIMIT');
    }
}
