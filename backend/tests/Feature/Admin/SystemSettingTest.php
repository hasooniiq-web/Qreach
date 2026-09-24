<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Services\OtpService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * These settings aren't decorative — they're actually read by OtpService
 * and StoreTagBatchRequest. This locks in that saving a setting genuinely
 * changes application behavior, not just a database row.
 */
class SystemSettingTest extends TestCase
{
    use RefreshDatabase;

    private function actingSuperAdmin(): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    public function test_updating_otp_cooldown_setting_changes_real_service_behavior(): void
    {
        $this->assertSame(60, OtpService::resendCooldownSeconds());

        $admin = $this->actingSuperAdmin();

        $this->actingAs($admin, 'admin')->put('/admin/settings', [
            'otp_code_ttl_minutes' => 5,
            'otp_resend_cooldown_seconds' => 90,
            'qr_batch_max_quantity' => 1000,
        ])->assertRedirect();

        $this->assertSame(90, OtpService::resendCooldownSeconds());
    }

    public function test_settings_page_requires_permission(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->get('/admin/settings')->assertStatus(403);
    }

    public function test_out_of_range_setting_is_rejected(): void
    {
        $admin = $this->actingSuperAdmin();

        $this->actingAs($admin, 'admin')->put('/admin/settings', [
            'otp_code_ttl_minutes' => 999,
            'otp_resend_cooldown_seconds' => 90,
            'qr_batch_max_quantity' => 1000,
        ])->assertSessionHasErrors('otp_code_ttl_minutes');

        $this->assertSame(5, OtpService::ttlMinutes());
    }
}
