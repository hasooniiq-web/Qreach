<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::query()
            ->with('user')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where('plate_number', 'like', $term)
                    ->orWhereHas('user', fn ($q) => $q->where('phone', 'like', $term));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->withCount([
                'tags as active_tags_count' => fn ($q) => $q->whereIn('status', \App\Models\Tag::CANCELLABLE_STATUSES),
                'tags as ended_tags_count' => fn ($q) => $q->whereIn('status', ['revoked', 'replaced', 'disabled']),
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load(['user', 'tags', 'contactRequests' => fn ($q) => $q->latest()->limit(10)]);

        return view('admin.vehicles.show', compact('vehicle'));
    }
}
