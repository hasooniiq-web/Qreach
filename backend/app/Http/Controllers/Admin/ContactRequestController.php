<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = ContactRequest::query()
            ->with('vehicle.user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.contact-requests.index', compact('requests'));
    }

    public function show(ContactRequest $contactRequest): View
    {
        if ($contactRequest->status === 'new') {
            $contactRequest->update(['status' => 'read', 'read_at' => now()]);
        }

        $contactRequest->load('vehicle.user', 'messages');

        return view('admin.contact-requests.show', ['contactRequest' => $contactRequest]);
    }

    public function close(ContactRequest $contactRequest): RedirectResponse
    {
        $contactRequest->update(['status' => 'closed']);

        return back()->with('status', 'تم إغلاق طلب التواصل.');
    }
}
