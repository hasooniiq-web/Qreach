<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Services\Recaptcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaLoginTest extends TestCase
{
    use RefreshDatabase;

    private function enableCaptcha(): void
    {
        config(['services.recaptcha.site_key' => 'site-key-123', 'services.recaptcha.secret_key' => 'secret-key-456']);
    }

    private function admin(): Admin
    {
        return Admin::factory()->create(['password' => 'a-long-password-123']);
    }

    public function test_without_keys_the_login_is_unchanged_and_shows_no_widget(): void
    {
        $admin = $this->admin();
        Http::fake();

        $this->get('/admin/login')->assertOk()->assertDontSee('g-recaptcha');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'a-long-password-123'])->assertRedirect();
        $this->assertAuthenticatedAs($admin, 'admin');
        Http::assertNothingSent();
    }

    public function test_with_keys_the_widget_and_google_hosts_appear_on_the_login_page_only(): void
    {
        $this->enableCaptcha();

        $login = $this->get('/admin/login')->assertOk()->assertSee('g-recaptcha')->assertSee('site-key-123');
        $this->assertStringContainsString('https://www.google.com/recaptcha/', $login->headers->get('Content-Security-Policy'));

        $other = $this->get('/admin/totp/challenge');
        $this->assertStringNotContainsString('google.com', (string) $other->headers->get('Content-Security-Policy'));
    }

    public function test_a_missing_token_is_refused_before_any_password_is_tried(): void
    {
        $this->enableCaptcha();
        $admin = $this->admin();
        Http::fake();

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'a-long-password-123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertSame(0, $admin->fresh()->failed_login_attempts, 'a bot must not lock a real admin out');
        Http::assertNothingSent();
    }

    public function test_google_rejecting_the_token_blocks_the_login(): void
    {
        $this->enableCaptcha();
        $admin = $this->admin();
        Http::fake([Recaptcha::VERIFY_URL => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'a-long-password-123', 'g-recaptcha-response' => 'bad'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_a_valid_token_lets_the_normal_login_proceed_and_google_gets_the_secret(): void
    {
        $this->enableCaptcha();
        $admin = $this->admin();
        Http::fake([Recaptcha::VERIFY_URL => Http::response(['success' => true])]);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'a-long-password-123', 'g-recaptcha-response' => 'good-token'])
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin, 'admin');
        Http::assertSent(fn ($request) => $request->url() === Recaptcha::VERIFY_URL
            && $request['secret'] === 'secret-key-456' && $request['response'] === 'good-token');
    }

    public function test_a_valid_captcha_does_not_bypass_the_password_check(): void
    {
        $this->enableCaptcha();
        $admin = $this->admin();
        Http::fake([Recaptcha::VERIFY_URL => Http::response(['success' => true])]);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong-password', 'g-recaptcha-response' => 'good-token'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertSame(1, $admin->fresh()->failed_login_attempts);
    }

    public function test_if_google_is_unreachable_the_login_fails_closed(): void
    {
        $this->enableCaptcha();
        $admin = $this->admin();
        Http::fake([Recaptcha::VERIFY_URL => fn () => throw new \Illuminate\Http\Client\ConnectionException('down')]);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'a-long-password-123', 'g-recaptcha-response' => 'good-token'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }
}
