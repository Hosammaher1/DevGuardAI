<?php

namespace App\Http\Controllers\GitHub;

use App\Domain\GitHub\GitHubWebhookVerifier;
use App\Domain\GitHub\RepositoryBindingService;
use App\Http\Controllers\Controller;
use App\Models\ProviderInbox;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class GitHubWebhookController extends Controller
{
    public function __construct(
        private readonly GitHubWebhookVerifier $verifier,
        private readonly RepositoryBindingService $repositoryService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();

        if (! $this->verifier->verifySignature($payload, $signature)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $event = (string) $request->header('X-GitHub-Event', '');
        $deliveryId = (string) $request->header('X-GitHub-Delivery', '');
        $data = $request->json()->all();
        $payloadHash = hash('sha256', $payload);

        // Deduplication & idempotency check if delivery ID is present
        if ($deliveryId !== '') {
            $existing = ProviderInbox::query()
                ->where('provider', 'github')
                ->where('delivery_id', $deliveryId)
                ->first();

            if ($existing !== null) {
                if ($existing->payload_hash !== $payloadHash) {
                    return response()->json(['error' => 'delivery_payload_conflict'], 409);
                }

                return response()->json([
                    'status' => 'duplicate_ignored',
                    'delivery_id' => $deliveryId,
                ], 200);
            }

            ProviderInbox::query()->create([
                'provider' => 'github',
                'delivery_id' => $deliveryId,
                'event_type' => $event,
                'payload_hash' => $payloadHash,
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        }

        if ($event === 'installation') {
            $action = $data['action'] ?? null;
            $installationId = $data['installation']['id'] ?? null;

            if (in_array($action, ['deleted', 'suspend'], true) && is_int($installationId)) {
                $this->repositoryService->revokeInstallation($installationId, "webhook_{$action}");
            }
        } elseif ($event === 'installation_repositories') {
            $action = $data['action'] ?? null;
            if ($action === 'removed' && ! empty($data['repositories_removed'])) {
                $removedRepoIds = array_column($data['repositories_removed'], 'id');
                Repository::query()
                    ->whereIn('github_repo_id', $removedRepoIds)
                    ->update(['status' => 'disconnected']);
            }
        }

        return response()->json([
            'status' => 'received',
            'event' => $event,
            'delivery_id' => $deliveryId ?: null,
        ]);
    }
}
