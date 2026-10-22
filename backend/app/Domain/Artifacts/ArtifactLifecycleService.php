<?php

namespace App\Domain\Artifacts;

use App\Models\Artifact;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final readonly class ArtifactLifecycleService
{
    public function purgeExpiredReports(CarbonImmutable $now): int
    {
        $deleted = 0;
        Artifact::query()
            ->where('artifact_type', 'report')
            ->where('expires_at', '<=', $now)
            ->orderBy('id')
            ->chunkById(100, function ($artifacts) use (&$deleted): void {
                foreach ($artifacts as $artifact) {
                    DB::transaction(function () use ($artifact, &$deleted): void {
                        $locked = Artifact::query()->whereKey($artifact->getKey())->lockForUpdate()->first();
                        if ($locked === null) {
                            return;
                        }
                        Storage::disk('private-artifacts')->delete($locked->storage_key);
                        $locked->delete();
                        $deleted++;
                    }, 3);
                }
            });

        return $deleted;
    }
}
