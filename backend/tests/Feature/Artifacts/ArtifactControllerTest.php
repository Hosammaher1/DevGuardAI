<?php

namespace Tests\Feature\Artifacts;

use App\Domain\Artifacts\ArtifactAccessService;
use App\Domain\Identity\Role;
use App\Models\ArtifactAccessLink;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ArtifactControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private-artifacts');
    }

    public function test_artifact_link_endpoints_require_authenticated_current_workspace_membership(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        [$attacker] = $this->workspaceWithOwner();
        $artifact = app(ArtifactAccessService::class)->store($workspace->getKey(), 'report', 'private.json', 'private', CarbonImmutable::now());

        $this->postJson("/artifacts/{$artifact->getKey()}/access-links")->assertUnauthorized();
        $this->actingAs($attacker)->postJson("/artifacts/{$artifact->getKey()}/access-links")->assertNotFound();

        $linkResponse = $this->actingAs($owner)->postJson("/artifacts/{$artifact->getKey()}/access-links")->assertCreated();
        $this->actingAs($attacker)->withHeader('Authorization', 'Artifact '.$linkResponse->json('token'))->getJson("/artifacts/{$artifact->getKey()}/content")->assertNotFound();
        $this->actingAs($owner)->withHeader('Authorization', 'Artifact '.$linkResponse->json('token'))->getJson("/artifacts/{$artifact->getKey()}/content")
            ->assertOk()
            ->assertJsonPath('contents', 'private');

        $linkId = ArtifactAccessLink::query()->where('artifact_id', $artifact->getKey())->value('id');
        $this->actingAs($attacker)->deleteJson("/artifacts/{$artifact->getKey()}/access-links/{$linkId}")->assertNotFound();
        $this->actingAs($owner)->deleteJson("/artifacts/{$artifact->getKey()}/access-links/{$linkId}")->assertNoContent();
        $this->actingAs($owner)->withHeader('Authorization', 'Artifact '.$linkResponse->json('token'))->getJson("/artifacts/{$artifact->getKey()}/content")->assertNotFound();
    }

    private function workspaceWithOwner(): array
    {
        $suffix = str()->random(8);
        $user = User::query()->create([
            'name' => 'Synthetic Artifact HTTP User',
            'email' => $suffix.'@example.test',
            'github_user_id' => 'github-artifact-http-'.$suffix,
        ]);
        $workspace = Workspace::query()->create(['name' => 'Synthetic Artifact HTTP Workspace '.$suffix, 'personal_owner_user_id' => $user->getKey()]);
        Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $user->getKey(),
            'role' => Role::Owner,
            'active' => true,
            'version' => 1,
        ]);

        return [$user, $workspace];
    }
}
