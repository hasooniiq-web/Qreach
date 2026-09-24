<?php

namespace Tests\Feature\Security;

use App\Models\Admin;
use App\Models\OtpCode;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QrBatchService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Section 45 security test cases not already covered by OtpSecurityTest,
 * RbacTest, TagActivationTest, VehicleAuthorizationTest, and ScanTest.
 * Each of these was checked against the actual implementation first
 * (not written blind) — see the docblock on each test for what code path
 * it locks in.
 */
class AdditionalSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * OTP brute force (section 45): OtpCode::attemptsExhausted() compares
     * `attempts` against `max_attempts` (5, set in OtpService::request()).
     * This proves the exhaustion check actually blocks a 6th attempt even
     * when it finally guesses the right code.
     */
    public function test_otp_verification_locks_out_after_max_attempts(): void
    {
        $phone = '+9647701112222';
        $code = '111222';

        OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => 'login',
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(5),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/otp/verify', ['phone' => $phone, 'code' => '000000'])
                ->assertStatus(422);
        }

        // The correct code, on the 6th attempt, must still be rejected.
        $this->postJson('/api/v1/auth/otp/verify', ['phone' => $phone, 'code' => $code])
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'تم تجاوز عدد المحاولات المسموح به. اطلب رمزًا جديدًا.');
    }

    /**
     * Deleted vehicle access (section 45): Vehicle uses SoftDeletes, and
     * Eloquent relations exclude soft-deleted models by default, so
     * ScanController::resolveActiveTag()'s `! $tag->vehicle` check should
     * already catch this. This proves it end to end rather than trusting
     * the framework default silently.
     */
    public function test_scanning_a_tag_whose_vehicle_was_soft_deleted_shows_generic_invalid_page(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)),
            'serial_number' => 'ST-Q-TEST-'.random_int(100000, 999999),
            'type' => 'qr',
            'status' => 'assigned',
            'vehicle_id' => $vehicle->id,
        ]);

        $vehicle->delete();

        $this->get("/t/{$tag->public_token}")->assertStatus(200)->assertViewIs('public.invalid');
    }

    /**
     * Broken property-level authorization (section 45): UpdateVehicleRequest
     * doesn't whitelist `user_id`, so $request->validated() can never carry
     * it through to $vehicle->update(). This locks in that a malicious
     * extra field in the payload is silently ignored, not just absent from
     * the validation rules.
     */
    public function test_customer_cannot_reassign_vehicle_ownership_via_update_payload(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create();

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/vehicles/{$vehicle->uuid}", [
                'nickname' => 'محاولة استحواذ',
                'user_id' => $attacker->id,
            ])
            ->assertStatus(200);

        $this->assertSame($owner->id, $vehicle->fresh()->user_id);
    }

    /**
     * Export authorization bypass (section 45): the export route already
     * carries admin.permission:qr.export middleware — this proves a
     * support_admin (which the seeder grants qr.view but not qr.export)
     * is actually denied when hitting the URL directly, not just kept
     * from seeing a link to it in the UI.
     */
    public function test_support_admin_cannot_export_tag_batch_via_direct_url(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $superAdmin = Admin::factory()->create();
        $superAdmin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);
        $batch = app(QrBatchService::class)->createBatch(1, $superAdmin->id, 'qr');

        $support = Admin::factory()->create();
        $support->roles()->sync([Role::where('slug', 'support_admin')->value('id')]);

        $this->actingAs($support, 'admin')
            ->get("/admin/batches/{$batch->id}/export")
            ->assertStatus(403);
    }

    /**
     * Same as above but for batch creation (RbacTest only covers the GET
     * /admin/batches/create form; this covers the POST that actually
     * generates tags).
     */
    public function test_support_admin_cannot_create_tag_batch_via_direct_post(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $support = Admin::factory()->create();
        $support->roles()->sync([Role::where('slug', 'support_admin')->value('id')]);

        $this->actingAs($support, 'admin')
            ->post('/admin/batches', ['tag_type' => 'qr', 'quantity' => 5])
            ->assertStatus(403);

        $this->assertDatabaseCount('tag_batches', 0);
    }

    /**
     * Session/token hijacking (section 45): OtpAuthController::logout()
     * deletes the current access token. This proves the deleted token is
     * actually rejected on the next request, not just removed from the
     * table while still being honored by stale middleware state.
     */
    public function test_logout_revokes_the_token_so_it_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Laravel's auth guards cache the resolved user on the guard
        // instance, and that instance persists across simulated requests
        // within a single test method (unlike a real php-fpm request,
        // which boots a fresh container). Forgetting the guard here makes
        // the next call re-resolve from the token exactly as a genuinely
        // new HTTP request would.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/vehicles')
            ->assertStatus(401);
    }
}
