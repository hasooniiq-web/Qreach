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

class EmergencyAndActivityTest extends TestCase
{
    use RefreshDatabase;

    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $vehicle = Vehicle::factory()->for(User::factory()->create())->create(['nickname' => 'سيارة الاختبار']);
        $this->tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-EM-1',
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
    }

    private function as(string $role): void
    {
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', $role)->value('id')]);
        $this->actingAs($admin, 'admin');
    }

    private function scan(string $action, array $payload = []): void
    {
        Scan::create([
            'tag_id' => $this->tag->id, 'vehicle_id' => $this->tag->vehicle_id, 'action_type' => $action,
            'payload' => $payload, 'status' => 'success', 'request_ip' => '203.0.113.7',
        ]);
    }

    public function test_emergency_page_lists_only_emergencies_with_counts_and_no_visitor_ip(): void
    {
        $this->as('support_admin'); // has emergency.view
        $this->scan('emergency', ['message' => 'حريق في المحرك']);
        $this->scan('vehicle_block');
        $this->scan('view');

        $this->get('/admin/emergencies')
            ->assertOk()
            ->assertSee('حريق في المحرك')
            ->assertSee('سيارة الاختبار')
            ->assertDontSee('203.0.113.7')
            ->assertViewHas('stats', fn ($s) => $s['total'] === 1 && $s['last_24h'] === 1);
    }

    public function test_emergency_page_requires_the_emergency_permission(): void
    {
        $this->as('reports_analyst');

        $this->get('/admin/emergencies')->assertForbidden();
    }

    public function test_dashboard_activity_feed_shows_real_actions_and_skips_page_views(): void
    {
        $this->as('super_admin');
        $this->scan('view');
        $this->scan('lights_on');
        ContactRequest::create([
            'vehicle_id' => $this->tag->vehicle_id, 'tag_id' => $this->tag->id, 'message' => 'x', 'request_ip' => '127.0.0.1',
        ]);

        $response = $this->get('/admin/dashboard')->assertOk()
            ->assertSee('الأضواء مضاءة')
            ->assertSee('طلب تواصل جديد')
            ->assertDontSee('عرض الصفحة');

        $this->assertCount(2, $response->viewData('activity'));
    }

    public function test_dashboard_activity_feed_is_empty_on_a_fresh_platform(): void
    {
        $this->as('super_admin');

        $this->get('/admin/dashboard')->assertOk()->assertSee('لا يوجد نشاط بعد');
    }
}
