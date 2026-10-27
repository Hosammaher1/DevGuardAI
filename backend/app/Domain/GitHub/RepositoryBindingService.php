<?php

namespace App\Domain\GitHub;

use App\Domain\Identity\CurrentMembershipAuthorization;
use App\Domain\Identity\WorkspaceAction;
use App\Models\GitHubAppInstallation;
use App\Models\Repository;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class RepositoryBindingService
{
    public function __construct(
        private readonly CurrentMembershipAuthorization $authorization,
        private readonly ?GitHubAppClient $appClient = null,
    ) {}

    /**
     * @return array<int, array{
     *     github_repo_id: int,
     *     name: string,
     *     full_name: string,
     *     owner_login: string,
     *     is_private: bool,
     *     default_branch: string,
     * }>
     */
    public function getAvailableRepositories(User $actor, Workspace $workspace, GitHubAppInstallation $installation): array
    {
        $decision = $this->authorization->decide(
            $actor->id,
            $workspace->id,
            $workspace->id,
            WorkspaceAction::ConnectRepositories,
        );

        if (! $decision->allowed) {
            throw new RepositoryAccessDenied($decision->reason);
        }

        if ($installation->workspace_id !== $workspace->id) {
            throw new RepositoryAccessDenied('installation_outside_workspace');
        }

        if ($installation->status !== 'active' || $installation->revoked_at !== null) {
            throw new RepositoryAccessDenied('installation_revoked');
        }

        $client = $this->appClient ?? app(GitHubAppClient::class);
        return $client->listInstallationRepositories((int) $installation->installation_id);
    }

    /**
     * @param  array{
     *     github_repo_id: int,
     *     name: string,
     *     full_name: string,
     *     owner_login: string,
     *     is_private?: bool,
     *     default_branch?: string,
     *     auto_scan_enabled?: bool,
     * }  $repoData
     */
    public function connectRepository(
        User $actor,
        Workspace $workspace,
        GitHubAppInstallation $installation,
        array $repoData,
    ): Repository {
        // Enforce RBAC
        $decision = $this->authorization->decide(
            $actor->id,
            $workspace->id,
            $workspace->id,
            WorkspaceAction::ConnectRepositories,
        );

        if (! $decision->allowed) {
            throw new RepositoryAccessDenied($decision->reason);
        }

        // Strict tenant isolation: installation must belong to the workspace
        if ($installation->workspace_id !== $workspace->id) {
            throw new RepositoryAccessDenied('installation_outside_workspace');
        }

        // Installation must be active
        if ($installation->status !== 'active' || $installation->revoked_at !== null) {
            throw new RepositoryAccessDenied('installation_revoked');
        }

        return DB::transaction(function () use ($actor, $workspace, $installation, $repoData): Repository {
            /** @var Repository $repo */
            $repo = Repository::query()->updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'github_repo_id' => $repoData['github_repo_id'],
                ],
                [
                    'github_app_installation_id' => $installation->id,
                    'name' => $repoData['name'],
                    'full_name' => $repoData['full_name'],
                    'owner_login' => $repoData['owner_login'],
                    'is_private' => $repoData['is_private'] ?? false,
                    'default_branch' => $repoData['default_branch'] ?? 'main',
                    'status' => 'connected',
                    'auto_scan_enabled' => $repoData['auto_scan_enabled'] ?? false,
                    'connected_by_user_id' => $actor->id,
                ],
            );

            return $repo;
        });
    }

    public function disconnectRepository(User $actor, Workspace $workspace, Repository $repository): void
    {
        $decision = $this->authorization->decide(
            $actor->id,
            $workspace->id,
            $workspace->id,
            WorkspaceAction::ConnectRepositories,
        );

        if (! $decision->allowed) {
            throw new RepositoryAccessDenied($decision->reason);
        }

        if ($repository->workspace_id !== $workspace->id) {
            throw new RepositoryAccessDenied('repository_outside_workspace');
        }

        $repository->update(['status' => 'disconnected']);
    }

    public function resolveBindingForCheckout(string $workspaceId, string $repositoryId): RepositoryBinding
    {
        /** @var Repository|null $repository */
        $repository = Repository::query()
            ->with('installation')
            ->where('workspace_id', $workspaceId)
            ->where('id', $repositoryId)
            ->first();

        if ($repository === null) {
            throw new RepositoryAccessDenied('repository_missing');
        }

        if ($repository->status !== 'connected') {
            throw new RepositoryAccessDenied('repository_disconnected');
        }

        $installation = $repository->installation;
        if ($installation === null || $installation->status !== 'active' || $installation->revoked_at !== null) {
            throw new RepositoryAccessDenied('stale_authority_denied');
        }

        return new RepositoryBinding(
            repositoryId: $repository->id,
            workspaceId: $repository->workspace_id,
            githubRepoId: $repository->github_repo_id,
            fullName: $repository->full_name,
            defaultBranch: $repository->default_branch,
            isPrivate: $repository->is_private,
            installationId: $installation->installation_id,
            status: $repository->status,
            permissions: $installation->permissions ?? [],
        );
    }

    public function revokeInstallation(int $githubInstallationId, ?string $reason = null): int
    {
        return DB::transaction(function () use ($githubInstallationId): int {
            $installations = GitHubAppInstallation::query()
                ->where('installation_id', $githubInstallationId)
                ->where('status', '!=', 'revoked')
                ->get();

            $count = 0;
            foreach ($installations as $installation) {
                $installation->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                ]);

                Repository::query()
                    ->where('github_app_installation_id', $installation->id)
                    ->update(['status' => 'revoked']);

                $count++;
            }

            return $count;
        });
    }
}
