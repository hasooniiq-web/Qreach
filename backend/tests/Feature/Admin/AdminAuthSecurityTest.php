<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for a real bug found during manual testing: Admin
 * was missing `locked_until` from $fillable, so the brute-force lockout
 * write in AuthController::login() was silently dropped — meaning the
 * 15-minute lockout after 5 failed attempts never actually took effect.
 */
class AdminAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_locks_after_five_failed_login_attempts(): void
    {
        $admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'lockout-test@qreach.app',
            'password' => 'CorrectPassword123!',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong-password']);
        }

        $admin->refresh();
        $this->assertNotNull($admin->locked_until, 'locked_until was never persisted — the lockout is not functional.');
        $this->assertTrue($admin->isLocked());
        $this->assertGuest('admin');
    }
}
