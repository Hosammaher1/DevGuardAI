<?php

namespace App\Domain\Artifacts;

use App\Domain\Identity\CurrentMembershipAuthorization;
use App\Domain\Identity\WorkspaceAction;
use App\Models\Artifact;
use App\Models\ArtifactAccessLink;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class ArtifactAccessService
{
    private const DISK = 'private-artifacts';

    public function __construct(private CurrentMembershipAuthorization $authorization) {}

    public function store(
        string $workspaceId,
        string $artifactType,
        string $originalName,
        string $contents,
        CarbonImmutable $createdAt,
        array $sensitiveValues = [],
        ?string $scanId = null,
    ): Artifact {
        $safeName = $this->safeOriginalName($originalName);
        $contents = $this->redactSensitiveValues($contents, $sensitiveValues);
        $storageKey = Str::ulid().'.enc';
        $ciphertext = Crypt::encryptString($contents);
        $disk = Storage::disk(self::DISK);

        if (! $disk->put($storageKey, $ciphertext)) {
            throw new \RuntimeException('artifact_storage_failed');
        }

        try {
            return DB::transaction(fn (): Artifact => Artifact::query()->create([
                'workspace_id' => $workspaceId,
                'scan_id' => $scanId,
                'artifact_type' => $artifactType,
                'original_name' => $safeName,
                'storage_key' => $storageKey,
                'ciphertext_sha256' => hash('sha256', $ciphertext),
                'plaintext_bytes' => strlen($contents),
                'expires_at' => $createdAt->addDays($this->retentionDays()),
                'created_at' => $createdAt,
            ]));
        } catch (\Throwable $exception) {
            $disk->delete($storageKey);
            throw $exception;
        }
    }

    public function issueLink(User $actor, Artifact $artifact, CarbonImmutable $now): IssuedArtifactLink
    {
        $decision = $this->authorization->decide(
            (int) $actor->getKey(),
            $artifact->workspace_id,
            $artifact->workspace_id,
            WorkspaceAction::ViewScanResults,
        );
        if (! $decision->allowed || $artifact->expires_at->lessThanOrEqualTo($now)) {
            throw new ArtifactAccessDenied;
        }

        $token = $this->newToken();
        $ttlSeconds = (int) config('devguard-artifacts.access_link_ttl_seconds', 300);
        if ($ttlSeconds < 1 || $ttlSeconds > 900) {
            throw new \LogicException('artifact_access_link_ttl_invalid');
        }
        $expiresAt = $now->addSeconds($ttlSeconds);
        $link = ArtifactAccessLink::query()->create([
            'artifact_id' => $artifact->getKey(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'created_at' => $now,
        ]);

        return new IssuedArtifactLink($link, $token, $expiresAt);
    }

    public function read(User $actor, string $artifactId, string $rawToken, CarbonImmutable $now): ?StoredArtifact
    {
        $artifact = Artifact::query()->whereKey($artifactId)->first();
        if ($artifact === null || $artifact->expires_at->lessThanOrEqualTo($now)) {
            return null;
        }

        $link = ArtifactAccessLink::query()
            ->where('artifact_id', $artifact->getKey())
            ->where('token_hash', hash('sha256', $rawToken))
            ->first();
        if ($link === null || $link->revoked_at !== null || $link->expires_at->lessThanOrEqualTo($now)) {
            return null;
        }

        $decision = $this->authorization->decide(
            (int) $actor->getKey(),
            $artifact->workspace_id,
            $artifact->workspace_id,
            WorkspaceAction::ViewScanResults,
        );
        if (! $decision->allowed) {
            return null;
        }

        $ciphertext = Storage::disk(self::DISK)->get($artifact->storage_key);
        if (! is_string($ciphertext) || ! hash_equals($artifact->ciphertext_sha256, hash('sha256', $ciphertext))) {
            return null;
        }

        try {
            return new StoredArtifact(Crypt::decryptString($ciphertext));
        } catch (\Throwable) {
            return null;
        }
    }

    public function revokeLink(User $actor, Artifact $artifact, string $linkId, CarbonImmutable $revokedAt): bool
    {
        $decision = $this->authorization->decide(
            (int) $actor->getKey(),
            $artifact->workspace_id,
            $artifact->workspace_id,
            WorkspaceAction::ViewScanResults,
        );
        if (! $decision->allowed) {
            return false;
        }

        return ArtifactAccessLink::query()
            ->whereKey($linkId)
            ->where('artifact_id', $artifact->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $revokedAt]) === 1;
    }

    public function delete(Artifact $artifact): void
    {
        DB::transaction(function () use ($artifact): void {
            $locked = Artifact::query()->whereKey($artifact->getKey())->lockForUpdate()->first();
            if ($locked === null) {
                return;
            }
            ArtifactAccessLink::query()->where('artifact_id', $locked->getKey())->update(['revoked_at' => now()]);
            Storage::disk(self::DISK)->delete($locked->storage_key);
            $locked->delete();
        }, 3);
    }

    private function retentionDays(): int
    {
        $days = (int) config('devguard-artifacts.report_retention_days', 30);
        if ($days < 1 || $days > 3650) {
            throw new \LogicException('artifact_retention_configuration_invalid');
        }

        return $days;
    }

    private function safeOriginalName(string $name): string
    {
        $leaf = basename(str_replace('\\', '/', $name));
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $leaf) ?: 'artifact.bin';

        return substr($safe, 0, 255);
    }

    /** @param list<string> $sensitiveValues */
    private function redactSensitiveValues(string $contents, array $sensitiveValues): string
    {
        foreach ($sensitiveValues as $value) {
            if (! is_string($value)) {
                throw new \InvalidArgumentException('artifact_sensitive_value_invalid');
            }
        }
        $sensitiveValues = array_values(array_filter($sensitiveValues, static fn (string $value): bool => $value !== ''));
        usort($sensitiveValues, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        return str_replace($sensitiveValues, array_fill(0, count($sensitiveValues), '[REDACTED]'), $contents);
    }

    private function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
