<?php

namespace App\Http\Controllers\Artifacts;

use App\Domain\Artifacts\ArtifactAccessDenied;
use App\Domain\Artifacts\ArtifactAccessService;
use App\Http\Controllers\Controller;
use App\Models\Artifact;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ArtifactController extends Controller
{
    public function createAccessLink(Request $request, string $artifact, ArtifactAccessService $access): JsonResponse
    {
        $record = Artifact::query()->whereKey($artifact)->first();
        if ($record === null) {
            return response()->json(['reason' => 'artifact_not_found'], 404);
        }

        try {
            $link = $access->issueLink($request->user(), $record, CarbonImmutable::now());
        } catch (ArtifactAccessDenied) {
            return response()->json(['reason' => 'artifact_not_found'], 404);
        }

        return response()->json([
            'artifact_id' => $record->getKey(),
            'token' => $link->token,
            'expires_at' => $link->expiresAt->toIso8601String(),
        ], 201);
    }

    public function content(Request $request, string $artifact, ArtifactAccessService $access): JsonResponse
    {
        $authorization = $request->header('Authorization', '');
        if (! is_string($authorization) || ! preg_match('/^Artifact ([A-Za-z0-9_-]{43})$/D', $authorization, $matches)) {
            return response()->json(['reason' => 'artifact_not_found'], 404);
        }
        $content = $access->read($request->user(), $artifact, $matches[1], CarbonImmutable::now());
        if ($content === null) {
            return response()->json(['reason' => 'artifact_not_found'], 404);
        }

        return response()->json([
            'artifact_id' => $artifact,
            'contents' => $content->contents,
        ]);
    }

    public function revokeAccessLink(Request $request, string $artifact, string $link, ArtifactAccessService $access): JsonResponse
    {
        $record = Artifact::query()->whereKey($artifact)->first();
        if ($record === null || ! $access->revokeLink($request->user(), $record, $link, CarbonImmutable::now())) {
            return response()->json(['reason' => 'artifact_not_found'], 404);
        }

        return response()->json([], 204);
    }
}
