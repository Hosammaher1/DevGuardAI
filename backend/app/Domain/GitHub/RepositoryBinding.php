<?php

namespace App\Domain\GitHub;

final readonly class RepositoryBinding
{
    /**
     * @param  array<string, string>  $permissions
     */
    public function __construct(
        public string $repositoryId,
        public string $workspaceId,
        public int $githubRepoId,
        public string $fullName,
        public string $defaultBranch,
        public bool $isPrivate,
        public int $installationId,
        public string $status,
        public array $permissions,
    ) {}
}
