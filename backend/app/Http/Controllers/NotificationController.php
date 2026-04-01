<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);

        $notifications = $user->notifications()
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($notifications->items())
                ->map(fn(DatabaseNotification $notification) => $this->transformNotification($notification))
                ->values(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'data' => [
                'count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return response()->json([
                'message' => 'Notification not found.',
            ], 404);
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
            $notification->refresh();
        }

        return response()->json([
            'data' => $this->transformNotification($notification),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $updated = $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'updated' => $updated,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformNotification(DatabaseNotification $notification): array
    {
        $payload = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => $notification->id,
            'type' => (string) ($payload['kind'] ?? $notification->type),
            'title' => (string) ($payload['title'] ?? ''),
            'body' => (string) ($payload['body'] ?? ''),
            'data' => $payload,
            'read_at' => optional($notification->read_at)?->toISOString(),
            'created_at' => optional($notification->created_at)?->toISOString(),
        ];
    }
}
