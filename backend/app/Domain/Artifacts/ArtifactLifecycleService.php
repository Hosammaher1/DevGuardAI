<?php

namespace App\Domain\Artifacts;

use App\Models\Artifact;
use App\Models\Scan;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ArtifactLifecycleService
{
    private const DISK = 'private-artifacts';

    public function purgeExpiredReports(CarbonImmutable $now): int
    {
        $disk = Storage::disk(self::DISK);
        $count = 0;

        Artifact::query()
            ->where('artifact_type', 'report')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->chunkById(100, function ($artifacts) use ($disk, &$count): void {
                foreach ($artifacts as $artifact) {
                    if ($this->deletePhysicalArtifactFile($disk, $artifact)) {
                        $artifact->delete();
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function reapExpiredArtifacts(Workspace $workspace, ?CarbonImmutable $now = null): int
    {
        $cutoff = $now ?? CarbonImmutable::now();
        $disk = Storage::disk(self::DISK);
        $count = 0;

        Artifact::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $cutoff)
            ->chunkById(100, function ($artifacts) use ($disk, &$count): void {
                foreach ($artifacts as $artifact) {
                    if ($this->deletePhysicalArtifactFile($disk, $artifact)) {
                        $artifact->delete();
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * Delete the physical artifact file from private storage disk.
     * Returns true if file was deleted or did not exist, false if deletion failed.
     */
    private function deletePhysicalArtifactFile($disk, Artifact $artifact): bool
    {
        if (empty($artifact->storage_key)) {
            return true;
        }

        try {
            if ($disk->exists($artifact->storage_key)) {
                $deleted = $disk->delete($artifact->storage_key);
                if (! $deleted) {
                    Log::error("Failed to delete physical artifact file from disk: {$artifact->storage_key}", [
                        'artifact_id' => $artifact->id,
                    ]);
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Exception deleting physical artifact file: {$e->getMessage()}", [
                'artifact_id' => $artifact->id,
                'storage_key' => $artifact->storage_key,
            ]);

            return false;
        }
    }

    public function cleanupScanTemporaryFiles(Scan $scan): void
    {
        $trustedPath = config('devguard-runner.trusted_project_path', base_path('../trusted-demo'));
        $tempScanDir = $trustedPath . DIRECTORY_SEPARATOR . 'test-results' . DIRECTORY_SEPARATOR . "scan-{$scan->id}";

        if (File::exists($tempScanDir)) {
            File::deleteDirectory($tempScanDir);
        }
    }
}
