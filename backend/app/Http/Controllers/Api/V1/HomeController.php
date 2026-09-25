<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactRequest;
use App\Models\Scan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One call for everything the home screen shows below the vehicle carousel:
 * the latest 3 conversations and four activity areas. All queries are scoped
 * to the caller's own vehicles / account. Notifications are deliberately NOT
 * here — the bell is the only notification surface.
 */
class HomeController extends Controller
{
    private const REASONS = [
        'damage' => 'ضرر في المركبة', 'malfunction' => 'عطل', 'safety' => 'مشكلة سلامة',
        'assistance' => 'طلب مساعدة', 'other' => 'سبب آخر', 'open' => 'باب أو نافذة مفتوحة', 'question' => 'استفسار',
    ];

    private const SCAN_LABELS = [
        'view' => 'تم مسح رمز مركبتك', 'contact_owner' => 'رسالة جديدة من زائر', 'important_message' => 'رسالة مهمة من زائر',
        'emergency' => 'تنبيه طارئ', 'vehicle_block' => 'تنبيه إعاقة طريق', 'lights_on' => 'تنبيه أضواء',
    ];

    private const ACCOUNT_LABELS = [
        'profile.name_updated' => 'حدّثت اسمك', 'profile.avatar_updated' => 'غيّرت صورتك', 'profile.contact_updated' => 'حدّثت رقمك',
        'profile.visibility_updated' => 'غيّرت ما يراه الزائر', 'profile.method_added' => 'أضفت طريقة تواصل',
        'profile.emergency_updated' => 'حدّثت جهة الطوارئ', 'vehicle.created' => 'أضفت مركبة', 'vehicle.updated' => 'عدّلت مركبة',
        'vehicle.deleted' => 'حذفت مركبة', 'qr.activated' => 'ربطت رمزًا بمركبة', 'tag.removed' => 'أزلت رمزًا',
    ];

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $owned = ContactRequest::query()->whereHas('vehicle', fn ($q) => $q->where('user_id', $user->id));

        $conversations = (clone $owned)->with('vehicle:id,nickname,plate_number')
            ->withCount('messages')
            ->orderByDesc('last_activity_at')->limit(3)->get();
        $last = fn (ContactRequest $c) => $c->messages()->latest('id')->first();

        $latestScans = Scan::query()->whereHas('vehicle', fn ($q) => $q->where('user_id', $user->id))
            ->latest('id')->limit(4)->get();

        $account = AuditLog::query()->where('actor_type', 'customer')->where('actor_id', $user->id)
            ->whereIn('action', array_keys(self::ACCOUNT_LABELS))->latest('id')->limit(4)->get();

        $reports = (clone $owned)->whereNotNull('reason')->orderByDesc('last_activity_at')->limit(3)->get();

        return response()->json([
            'conversations' => $conversations->map(function (ContactRequest $c) use ($last) {
                $m = $last($c);

                return [
                    'id' => $c->uuid,
                    'visitor_name' => $c->visitor_name,
                    'vehicle' => $c->vehicle?->nickname ?: $c->vehicle?->plate_number,
                    'preview' => $m?->body ?? $c->message,
                    'from_owner' => $m?->sender === 'owner',
                    'status' => $c->status,
                    'unread' => $c->status === 'new',
                    'at' => ($c->last_activity_at ?? $c->created_at)->toIso8601String(),
                ];
            })->values(),
            'activities' => [
                'latest' => $latestScans->map(fn (Scan $s) => [
                    'text' => self::SCAN_LABELS[$s->action_type] ?? 'نشاط على مركبتك',
                    'at' => $s->created_at->toIso8601String(),
                ])->values(),
                'communication' => $conversations->map(function (ContactRequest $c) use ($last) {
                    $m = $last($c);

                    return [
                        'text' => ($m?->sender === 'owner' ? 'ردّك: ' : 'الزائر: ').($m?->body ?? $c->message),
                        'at' => ($c->last_activity_at ?? $c->created_at)->toIso8601String(),
                    ];
                })->take(3)->values(),
                'account' => $account->map(fn (AuditLog $a) => [
                    'text' => self::ACCOUNT_LABELS[$a->action] ?? 'تحديث على حسابك',
                    'at' => $a->created_at->toIso8601String(),
                ])->values(),
                'reports' => $reports->map(fn (ContactRequest $c) => [
                    'text' => (self::REASONS[$c->reason] ?? 'بلاغ').' — '.match ($c->status) {
                        'new' => 'جديد', 'read' => 'تمت القراءة', 'responded' => 'تم الرد', default => 'مغلق',
                    },
                    'at' => ($c->last_activity_at ?? $c->created_at)->toIso8601String(),
                ])->values(),
            ],
            'unread_messages' => (clone $owned)->where('status', 'new')->count(),
        ]);
    }
}
