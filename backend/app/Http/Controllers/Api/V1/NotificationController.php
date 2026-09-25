<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's in-app notification feed (backed by Laravel's database
 * notifications, written when a visitor acts on a scanned tag). Strictly
 * scoped to the authenticated user: every query goes through
 * $request->user()->notifications(), so another user's notification id
 * simply isn't found.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $page = $user->notifications()->latest()->paginate(20);

        // One lookup for the vehicles named in this page, instead of one per row.
        $vehicles = Vehicle::whereIn('id', $page->getCollection()->pluck('data.vehicle_id')->filter()->unique())
            ->get(['id', 'nickname', 'plate_number'])->keyBy('id');

        $items = $page->getCollection()->map(function ($n) use ($vehicles) {
            $vehicle = $vehicles->get($n->data['vehicle_id'] ?? null);

            return [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'other',
                'action_type' => $n->data['action_type'] ?? null,
                'title' => $n->data['title'] ?? 'تنبيه',
                'message' => $n->data['message'] ?? null,
                'visitor_name' => $n->data['visitor_name'] ?? null,
                'vehicle' => $vehicle ? ($vehicle->nickname ?: $vehicle->plate_number) : null,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $items,
            'unread_count' => $user->unreadNotifications()->count(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if (! $notification) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'الإشعار غير موجود.']], 404);
        }

        $notification->markAsRead();

        return response()->json(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }
}
