<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Who invited whom, and which invitations have paid their reward. */
class ReferralController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    public const STATUS_LABELS = [
        'pending' => 'بانتظار اشتراك الصديق',
        'rewarded' => 'تمت المكافأة',
        'capped' => 'تجاوز الحد الأقصى',
    ];

    public function index(Request $request, ReferralService $referrals): View|\Illuminate\Http\Response
    {
        $status = array_key_exists($request->string('status')->toString(), self::STATUS_LABELS)
            ? $request->string('status')->toString()
            : null;

        $query = Referral::query()
            ->with(['referrer', 'referred'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id');

        if ($export = $this->exportRows($request, 'referrals', 'الدعوات', ['الداعي', 'هاتف الداعي', 'المدعو', 'هاتف المدعو', 'الحالة', 'الأيام الممنوحة', 'تاريخ الدعوة', 'تاريخ المكافأة'], $query,
            fn ($r) => [
                (string) $r->referrer?->display_name,
                (string) $r->referrer?->phone,
                (string) $r->referred?->display_name,
                (string) $r->referred?->phone,
                self::STATUS_LABELS[$r->status] ?? $r->status,
                $r->reward_days !== null ? (string) $r->reward_days : '—',
                \App\Support\Export\Labels::date($r->created_at, true),
                \App\Support\Export\Labels::date($r->rewarded_at, true),
            ],
            filters: $status ? ['الحالة: '.self::STATUS_LABELS[$status]] : [])) {
            return $export;
        }

        return view('admin.subscriptions.referrals', [
            'referrals' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'stats' => [
                'total' => Referral::count(),
                'rewarded' => Referral::where('status', 'rewarded')->count(),
                'pending' => Referral::where('status', 'pending')->count(),
                'days' => (int) Referral::where('status', 'rewarded')->sum('reward_days'),
            ],
            'rewardDays' => $referrals->rewardDays(),
            'maxRewards' => $referrals->maxRewards(),
        ]);
    }
}
