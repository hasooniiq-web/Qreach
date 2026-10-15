<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $vehicles = Vehicle::query()
            ->with('user')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $phone = \App\Support\PhoneSearch::pattern($request->string('q')->toString());
                $query->where(fn ($w) => $w->where('plate_number', 'like', $term)
                    ->orWhereHas('user', fn ($q) => $q->where('phone', 'like', $phone ?? $term)));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->withCount([
                'tags as active_tags_count' => fn ($q) => $q->whereIn('status', \App\Models\Tag::CANCELLABLE_STATUSES),
                'tags as ended_tags_count' => fn ($q) => $q->whereIn('status', ['revoked', 'replaced', 'disabled']),
            ])
            ->latest();

        if ($export = $this->exportRows($request, 'vehicles', 'المركبات', ['المركبة', 'رقم اللوحة', 'الشركة والطراز', 'سنة الصنع', 'المالك', 'هاتف المالك', 'الرمز النشط', 'الحالة'], $vehicles,
            fn ($v) => [
                (string) ($v->nickname ?: '—'),
                (string) $v->plate_number,
                trim($v->make.' '.$v->model) ?: '—',
                $v->year ? (string) $v->year : '—',
                $v->user?->display_name ?? '—',
                (string) ($v->user?->phone ?? ''),
                (int) $v->active_tags_count,
                \App\Support\Export\Labels::status($v->status),
            ],
            filters: $this->filterLines($request, ['q' => 'بحث', 'status' => 'الحالة'], ['status' => ['active' => 'نشط', 'inactive' => 'موقوف']]))) {
            return $export;
        }

        $vehicles = $vehicles->paginate(20)->withQueryString();

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load(['user', 'tags', 'contactRequests' => fn ($q) => $q->latest()->limit(10)]);

        return view('admin.vehicles.show', compact('vehicle'));
    }
}
