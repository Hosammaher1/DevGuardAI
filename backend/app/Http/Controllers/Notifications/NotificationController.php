<?php

namespace App\Http\Controllers\Notifications;

use App\Domain\Identity\CurrentMembershipAuthorization;
use App\Domain\Identity\WorkspaceAction;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly CurrentMembershipAuthorization $authorization,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $decision = $this->authorization->decide(
            (int) $request->user()->getKey(),
            $workspace->id,
            $workspace->id,
            WorkspaceAction::ViewProjectsAndReports,
        );

        if (! $decision->allowed) {
            return response()->json(['reason' => $decision->reason], 403);
        }

        $notifications = Notification::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->whereNull('read_at')->count(),
        ]);
    }

    public function markAsRead(Request $request, Workspace $workspace, Notification $notification): JsonResponse
    {
        if ($notification->workspace_id !== $workspace->id || $notification->user_id !== $request->user()->id) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['status' => 'marked_read']);
    }
}
