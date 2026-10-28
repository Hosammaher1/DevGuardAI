<?php

namespace App\Domain\Demo;

use App\Domain\Identity\Role;
use App\Models\CreditAccount;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DemoWorkspaceProvisioner
{
    /**
     * Provision or retrieve the isolated private staging workspace, user, and initial credits.
     *
     * @return array{user: User, workspace: Workspace}
     */
    public function provision(): array
    {
        return DB::transaction(function (): array {
            $user = $this->ensureStagingUser();
            $workspace = $this->ensureStagingWorkspace($user);
            $this->ensureStagingMembership($workspace, $user);
            $this->ensureStagingCredits($workspace);

            return [
                'user' => $user,
                'workspace' => $workspace,
            ];
        });
    }

    private function ensureStagingUser(): User
    {
        $githubId = (string) config('devguard-demo.user_github_id', 'staging-authorized-user');
        $user = User::query()->where('github_user_id', $githubId)->first();

        if ($user === null) {
            $user = User::query()->create([
                'github_user_id' => $githubId,
                'name' => (string) config('devguard-demo.user_name', 'Staging Engineer'),
                'email' => (string) config('devguard-demo.user_email', 'staging@devguard.ai'),
            ]);
        }

        return $user;
    }

    private function ensureStagingWorkspace(User $user): Workspace
    {
        $name = (string) config('devguard-demo.workspace_name', 'DevGuard Staging Workspace');
        $workspace = Workspace::query()->where('name', $name)->first();

        if ($workspace === null) {
            $workspace = Workspace::query()->create([
                'name' => $name,
                'personal_owner_user_id' => $user->id,
            ]);
        }

        return $workspace;
    }

    private function ensureStagingMembership(Workspace $workspace, User $user): Membership
    {
        $membership = Membership::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            $membership = Membership::query()->create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => Role::Owner,
                'active' => true,
                'version' => 1,
            ]);
        }

        return $membership;
    }

    private function ensureStagingCredits(Workspace $workspace): void
    {
        $kinds = [
            'scan' => 50,
            'fix' => 20,
        ];

        foreach ($kinds as $kind => $available) {
            $account = CreditAccount::query()
                ->where('workspace_id', $workspace->id)
                ->where('credit_kind', $kind)
                ->first();

            if ($account === null) {
                CreditAccount::query()->create([
                    'workspace_id' => $workspace->id,
                    'credit_kind' => $kind,
                    'available_credits' => $available,
                    'reserved_credits' => 0,
                    'consumed_credits' => 0,
                    'created_at' => CarbonImmutable::now(),
                    'updated_at' => CarbonImmutable::now(),
                ]);
            }
        }
    }
}
