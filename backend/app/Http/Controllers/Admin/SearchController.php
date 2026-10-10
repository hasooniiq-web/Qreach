<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TagTokenGenerator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The topbar's global search: one query across the records an admin may
 * open. Each group only appears (and is only queried) if the admin holds
 * that area's view permission, so search never reveals more than the lists do.
 *
 * Matching is case-insensitive (PostgreSQL's LIKE is not, so both sides are
 * lower-cased) and a phone can be typed the way people write it — 0770…, 770…,
 * +964 770… or Arabic-Indic digits — because the stored form is +964….
 */
class SearchController extends Controller
{
    private const ARABIC_DIGITS = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'];

    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $admin = $request->user('admin');

        $like = '%'.mb_strtolower(str_replace(['%', '_'], ['\\%', '\\_'], $q)).'%';
        $phoneLike = $this->phonePattern($q);

        $results = ['users' => collect(), 'vehicles' => collect(), 'tags' => collect()];

        if (mb_strlen($q) >= 2) {
            if ($admin->hasPermission('users.view')) {
                $results['users'] = User::where(function ($w) use ($like, $phoneLike) {
                    $w->whereRaw('LOWER(name) LIKE ?', [$like]);
                    if ($phoneLike) {
                        $w->orWhere('phone', 'like', $phoneLike);
                    }
                })->latest()->limit(8)->get();
            }

            if ($admin->hasPermission('vehicles.view')) {
                $results['vehicles'] = Vehicle::with('user')
                    ->where(function ($w) use ($like, $phoneLike) {
                        $w->whereRaw('LOWER(plate_number) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(nickname) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(make) LIKE ?', [$like]);
                        if ($phoneLike) {
                            $w->orWhereHas('user', fn ($u) => $u->where('phone', 'like', $phoneLike));
                        }
                    })
                    ->latest()->limit(8)->get();
            }

            if ($admin->hasPermission('qr.view')) {
                $results['tags'] = Tag::where(function ($w) use ($like, $q) {
                    $w->whereRaw('LOWER(serial_number) LIKE ?', [$like])
                        ->orWhere('public_token', TagTokenGenerator::normalize($q))
                        ->orWhere('public_token', $q);
                })->latest()->limit(8)->get();
            }
        }

        return view('admin.search', ['q' => $q, 'results' => $results, 'total' => collect($results)->sum(fn ($c) => $c->count())]);
    }

    /** "0770 123 4567" / "+964 770…" / "٠٧٧٠…" → a LIKE pattern on the local digits, or null if it isn't phone-like. */
    private function phonePattern(string $q): ?string
    {
        $digits = preg_replace('/\D/', '', strtr($q, self::ARABIC_DIGITS));
        if (strlen($digits) < 4) {
            return null;
        }

        $local = ltrim(preg_replace('/^964/', '', $digits), '0');

        return $local === '' ? null : '%'.$local.'%';
    }
}
