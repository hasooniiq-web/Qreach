<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('phone', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->withCount('vehicles')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load(['profile', 'contactMethods', 'vehicles.tags', 'devices']);

        return view('admin.users.show', compact('user'));
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $user->update(['status' => 'suspended']);

        AuditLogger::log('admin.user_suspended', 'admin', $request->user('admin')->id, 'User', $user->id, 'success', [], $request);

        return back()->with('status', 'تم إيقاف حساب المستخدم.');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $user->update(['status' => 'active']);

        AuditLogger::log('admin.user_activated', 'admin', $request->user('admin')->id, 'User', $user->id, 'success', [], $request);

        return back()->with('status', 'تم تفعيل حساب المستخدم.');
    }
}
