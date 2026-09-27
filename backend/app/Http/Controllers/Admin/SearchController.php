<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The topbar's global search: one query across the records an admin may
 * open. Each group only appears (and is only queried) if the admin holds
 * that area's view permission, so search never reveals more than the lists do.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $admin = $request->user('admin');
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';

        $results = ['users' => collect(), 'vehicles' => collect(), 'tags' => collect()];

        if (mb_strlen($q) >= 2) {
            if ($admin->hasPermission('users.view')) {
                $results['users'] = User::where(fn ($w) => $w->where('phone', 'like', $like)->orWhere('name', 'like', $like))
                    ->latest()->limit(8)->get();
            }
            if ($admin->hasPermission('vehicles.view')) {
                $results['vehicles'] = Vehicle::with('user')
                    ->where(fn ($w) => $w->where('plate_number', 'like', $like)->orWhere('nickname', 'like', $like)->orWhere('make', 'like', $like))
                    ->latest()->limit(8)->get();
            }
            if ($admin->hasPermission('qr.view')) {
                $results['tags'] = Tag::where('serial_number', 'like', $like)->latest()->limit(8)->get();
            }
        }

        return view('admin.search', ['q' => $q, 'results' => $results, 'total' => collect($results)->sum(fn ($c) => $c->count())]);
    }
}
