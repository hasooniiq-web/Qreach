<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\ContactRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QrBatchService;
use App\Services\SecurityEventLogger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Before this test existed, most admin pages (dashboard, users, vehicles...)
 * had no test that ever rendered them, so a Blade error in a redesign would
 * have shipped silently. It also locks in a CSP regression found during the
 * redesign: the app sends `script-src 'self'`, which blocks inline
 * onchange/onsubmit attributes, so views must use data-* attributes handled
 * by admin-ui.js instead.
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function seedAndListPages(): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);
        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $batch = app(QrBatchService::class)->createBatch(2, $admin->id, 'qr');
        $request = ContactRequest::create([
            'vehicle_id' => $vehicle->id,
            'tag_id' => $batch->tags()->first()->id,
            'visitor_name' => 'زائر',
            'message' => 'مرحبا',
            'request_ip' => '127.0.0.1',
        ]);
        SecurityEventLogger::log(SecurityEventLogger::QR_GUESSING, 'warning');

        return [
            'dashboard' => '/admin/dashboard',
            'users' => '/admin/users',
            'user detail' => "/admin/users/{$user->uuid}",
            'vehicles' => '/admin/vehicles',
            'vehicle detail' => "/admin/vehicles/{$vehicle->uuid}",
            'tags' => '/admin/qr',
            'batches' => '/admin/batches',
            'new batch' => '/admin/batches/create',
            'batch detail' => "/admin/batches/{$batch->id}",
            'contact requests' => '/admin/contact-requests',
            'contact request detail' => "/admin/contact-requests/{$request->id}",
            'analytics' => '/admin/analytics',
            'security' => '/admin/security',
            'audit' => '/admin/audit',
            'admins' => '/admin/admins',
            'new admin' => '/admin/admins/create',
            'settings' => '/admin/settings',
            'subscriptions' => '/admin/subscriptions',
            'upgrade requests' => '/admin/subscriptions/requests',
            'plans' => '/admin/plans',
            'profile' => '/admin/profile',
            'reports' => '/admin/reports',
            'contact request report' => '/admin/reports?report=contact_requests',
        ];
    }

    public function test_every_admin_page_renders_and_uses_no_inline_event_handlers(): void
    {
        foreach ($this->seedAndListPages() as $name => $path) {
            $response = $this->get($path);

            $this->assertSame(200, $response->getStatusCode(), "{$name} ({$path}) did not render");

            $html = $response->getContent();
            $this->assertStringNotContainsString(' onchange=', $html, "{$name} has an inline onchange the CSP blocks");
            $this->assertStringNotContainsString(' onsubmit=', $html, "{$name} has an inline onsubmit the CSP blocks");
            $this->assertStringNotContainsString(' onclick=', $html, "{$name} has an inline onclick the CSP blocks");
        }
    }

    public function test_security_page_shows_real_summary_counts(): void
    {
        $this->seedAndListPages();

        $this->get('/admin/security')
            ->assertOk()
            ->assertSee('تحذيرات مفتوحة')
            ->assertSee('محاولة تخمين رمز QR');
    }
}
