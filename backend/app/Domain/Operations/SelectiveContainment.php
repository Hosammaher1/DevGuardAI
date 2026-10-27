<?php

namespace App\Domain\Operations;

use Illuminate\Support\Facades\Cache;

final class SelectiveContainment
{
    private const KEY_PREFIX = 'devguard:containment:';

    public function isScansAllowed(): bool
    {
        return ! (bool) Cache::get(self::KEY_PREFIX . 'scans_disabled', false);
    }

    public function isFixesAllowed(): bool
    {
        return ! (bool) Cache::get(self::KEY_PREFIX . 'fixes_disabled', false);
    }

    public function isGitHubWritesAllowed(): bool
    {
        return ! (bool) Cache::get(self::KEY_PREFIX . 'github_writes_disabled', false);
    }

    public function isRunnersAllowed(): bool
    {
        return ! (bool) Cache::get(self::KEY_PREFIX . 'runners_disabled', false);
    }

    public function disableScans(string $reason, ?string $operator = 'operator'): void
    {
        Cache::forever(self::KEY_PREFIX . 'scans_disabled', true);
        Cache::forever(self::KEY_PREFIX . 'scans_reason', $reason);
    }

    public function enableScans(): void
    {
        Cache::forget(self::KEY_PREFIX . 'scans_disabled');
        Cache::forget(self::KEY_PREFIX . 'scans_reason');
    }

    public function disableFixes(string $reason, ?string $operator = 'operator'): void
    {
        Cache::forever(self::KEY_PREFIX . 'fixes_disabled', true);
        Cache::forever(self::KEY_PREFIX . 'fixes_reason', $reason);
    }

    public function enableFixes(): void
    {
        Cache::forget(self::KEY_PREFIX . 'fixes_disabled');
        Cache::forget(self::KEY_PREFIX . 'fixes_reason');
    }

    public function disableGitHubWrites(string $reason, ?string $operator = 'operator'): void
    {
        Cache::forever(self::KEY_PREFIX . 'github_writes_disabled', true);
        Cache::forever(self::KEY_PREFIX . 'github_writes_reason', $reason);
    }

    public function enableGitHubWrites(): void
    {
        Cache::forget(self::KEY_PREFIX . 'github_writes_disabled');
        Cache::forget(self::KEY_PREFIX . 'github_writes_reason');
    }

    public function disableRunners(string $reason, ?string $operator = 'operator'): void
    {
        Cache::forever(self::KEY_PREFIX . 'runners_disabled', true);
        Cache::forever(self::KEY_PREFIX . 'runners_reason', $reason);
    }

    public function enableRunners(): void
    {
        Cache::forget(self::KEY_PREFIX . 'runners_disabled');
        Cache::forget(self::KEY_PREFIX . 'runners_reason');
    }

    /**
     * @return array<string, bool>
     */
    public function getStatus(): array
    {
        return [
            'scans_allowed' => $this->isScansAllowed(),
            'fixes_allowed' => $this->isFixesAllowed(),
            'github_writes_allowed' => $this->isGitHubWritesAllowed(),
            'runners_allowed' => $this->isRunnersAllowed(),
        ];
    }
}
