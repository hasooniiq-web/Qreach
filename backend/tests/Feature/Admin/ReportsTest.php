<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\ContactRequest;
use App\Models\Role;
use App\Models\Scan;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $vehicle = Vehicle::factory()->for(User::factory()->create())->create(['plate_number' => '12345']);
        $this->tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-REP-1',
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
    }

    private function as(string $role): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', $role)->value('id')]);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    private function scan(string $action, $when = null): Scan
    {
        $scan = Scan::create([
            'tag_id' => $this->tag->id, 'vehicle_id' => $this->tag->vehicle_id, 'action_type' => $action,
            'status' => 'success', 'request_ip' => '203.0.113.9', 'user_agent' => 'TestAgent/1.0',
        ]);
        if ($when) {
            $scan->forceFill(['created_at' => $when])->save();
        }

        return $scan;
    }

    public function test_scan_report_shows_real_counts_and_filters_by_action_and_date(): void
    {
        $this->as('reports_analyst');
        $this->scan('view');
        $this->scan('view');
        $this->scan('emergency');
        $this->scan('view', now()->subDays(60));

        $all = $this->get('/admin/reports')->assertOk();
        $all->assertViewHas('total', 3); // the 60-day-old scan is outside the default 30-day window

        $this->get('/admin/reports?action=emergency')->assertViewHas('total', 1);
        $this->get('/admin/reports?from='.now()->subDays(90)->toDateString())->assertViewHas('total', 4);
    }

    public function test_report_and_export_omit_ip_addresses_and_user_agents(): void
    {
        $this->as('reports_analyst');
        $this->scan('view');

        $this->get('/admin/reports')->assertOk()->assertDontSee('203.0.113.9')->assertDontSee('TestAgent');

        $csv = $this->get('/admin/reports/export')->assertOk()->streamedContent();
        $this->assertStringNotContainsString('203.0.113.9', $csv);
        $this->assertStringNotContainsString('TestAgent', $csv);
        $this->assertStringContainsString('12345', $csv);
        $this->assertStringContainsString('ST-Q-REP-1', $csv);
    }

    public function test_export_neutralizes_spreadsheet_formulas_and_is_audit_logged(): void
    {
        $admin = $this->as('reports_analyst');
        ContactRequest::create([
            'vehicle_id' => $this->tag->vehicle_id, 'tag_id' => $this->tag->id,
            'message' => '=HYPERLINK("http://evil.example","click")', 'request_ip' => '127.0.0.1',
        ]);

        $csv = $this->get('/admin/reports/export?report=contact_requests')->assertOk()->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported', 'actor_id' => $admin->id]);
    }

    public function test_contact_request_report_filters_by_status(): void
    {
        $this->as('reports_analyst');
        foreach (['new', 'new', 'closed'] as $status) {
            ContactRequest::create([
                'vehicle_id' => $this->tag->vehicle_id, 'tag_id' => $this->tag->id,
                'message' => 'm', 'status' => $status, 'request_ip' => '127.0.0.1',
            ]);
        }

        $this->get('/admin/reports?report=contact_requests')->assertViewHas('total', 3);
        $this->get('/admin/reports?report=contact_requests&status=closed')->assertViewHas('total', 1);
    }

    public function test_reports_require_permission_and_export_needs_its_own(): void
    {
        $this->as('support_admin');
        $this->get('/admin/reports')->assertForbidden();
        $this->get('/admin/reports/export')->assertForbidden();
    }

    public function test_invalid_input_falls_back_to_safe_defaults(): void
    {
        $this->as('reports_analyst');

        $this->get('/admin/reports?report=users;drop&from=not-a-date&to=%00&action=x')->assertOk()->assertViewHas('report', 'scans');
    }
}
