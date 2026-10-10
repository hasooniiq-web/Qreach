<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use Tests\TestCase;

/** Every admin must enrol an authenticator and pass it on each sign-in. */
class AdminTotpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.require_totp' => true]);
    }

    private function admin(array $attrs = []): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create(['password' => 'a-long-password-123'] + $attrs);
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    public function test_an_admin_without_an_authenticator_is_sent_to_enrolment_before_anything_else(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'a-long-password-123'])->assertRedirect();
        $this->get('/admin/dashboard')->assertRedirect(route('admin.totp.setup'));
        $this->get('/admin/users')->assertRedirect(route('admin.totp.setup'));
    }

    public function test_the_setup_page_shows_a_qr_and_the_manual_key(): void
    {
        $admin = $this->admin();

        $page = $this->actingAs($admin, 'admin')->get('/admin/totp/setup')->assertOk();
        $page->assertSee('data:image/svg+xml;base64,', false)->assertSee('رمز التحقق');
    }

    public function test_a_wrong_code_does_not_enable_it_and_the_right_code_does(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->get('/admin/totp/setup')->assertOk();
        $secret = session('admin_totp_pending_secret');

        $this->post('/admin/totp/setup', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($admin->fresh()->totp_enabled);

        $this->post('/admin/totp/setup', ['code' => Google2FA::getCurrentOtp($secret)])->assertRedirect(route('admin.dashboard'));

        $admin->refresh();
        $this->assertTrue($admin->totp_enabled);
        $this->assertSame($secret, $admin->totp_secret);
        $this->assertStringNotContainsString($secret, \DB::table('admins')->where('id', $admin->id)->value('totp_secret'));
        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_every_sign_in_asks_for_the_code_again(): void
    {
        $secret = Google2FA::generateSecretKey();
        $admin = $this->admin(['totp_enabled' => true, 'totp_secret' => $secret]);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'a-long-password-123'])
            ->assertRedirect(route('admin.totp.challenge'));
        $this->get('/admin/dashboard')->assertRedirect(route('admin.totp.challenge'));

        $this->post('/admin/totp/verify', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->get('/admin/dashboard')->assertRedirect(route('admin.totp.challenge'));

        $this->post('/admin/totp/verify', ['code' => Google2FA::getCurrentOtp($secret)])->assertRedirect();
        $this->get('/admin/dashboard')->assertOk();

        // A new sign-in in the same browser session must not inherit the earlier verification.
        $this->post('/admin/logout');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'a-long-password-123']);
        $this->get('/admin/dashboard')->assertRedirect(route('admin.totp.challenge'));
    }

    public function test_a_super_admin_can_reset_another_admins_authenticator(): void
    {
        $boss = $this->admin();
        $other = Admin::factory()->create(['totp_enabled' => true, 'totp_secret' => Google2FA::generateSecretKey()]);
        config(['admin.require_totp' => false]);

        $this->actingAs($boss, 'admin')->post("/admin/admins/{$other->id}/totp/reset")->assertSessionHas('status');

        $other->refresh();
        $this->assertFalse($other->totp_enabled);
        $this->assertNull($other->totp_secret);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.totp_reset']);

        // Not for yourself.
        $this->post("/admin/admins/{$boss->id}/totp/reset")->assertSessionHasErrors('admin');
    }
}
