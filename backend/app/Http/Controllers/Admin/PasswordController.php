<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Forced password change on first login (bootstrap admin) and voluntary
 * password changes. Re-authentication is required for this sensitive
 * operation per section 23.
 */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('admin.auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'confirmed', 'min:12'],
        ], [], ['password' => 'كلمة المرور الجديدة']);

        $admin = auth('admin')->user();
        $admin->forceFill([
            'password' => Hash::make($request->string('password')),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        AuditLogger::log('admin.password_changed', 'admin', $admin->id, 'Admin', $admin->id, 'success', [], $request);

        return redirect()->route('admin.dashboard')->with('status', 'تم تحديث كلمة المرور بنجاح.');
    }
}
