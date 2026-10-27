<?php

namespace Tests\Integration\Operations;

use App\Domain\Operations\SelectiveContainment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class SelectiveContainmentTest extends TestCase
{
    use RefreshDatabase;

    private SelectiveContainment $containment;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->containment = new SelectiveContainment();
    }

    public function test_all_controls_are_allowed_by_default(): void
    {
        $status = $this->containment->getStatus();
        $this->assertTrue($status['scans_allowed']);
        $this->assertTrue($status['fixes_allowed']);
        $this->assertTrue($status['github_writes_allowed']);
        $this->assertTrue($status['runners_allowed']);
    }

    public function test_controls_operate_independently(): void
    {
        $this->containment->disableScans('Security maintenance');

        $this->assertFalse($this->containment->isScansAllowed());
        $this->assertTrue($this->containment->isFixesAllowed());
        $this->assertTrue($this->containment->isGitHubWritesAllowed());
        $this->assertTrue($this->containment->isRunnersAllowed());

        $this->containment->enableScans();
        $this->assertTrue($this->containment->isScansAllowed());
    }

    public function test_emergency_containment_of_github_writes(): void
    {
        $this->containment->disableGitHubWrites('Unauthorized PR rate');
        $this->assertFalse($this->containment->isGitHubWritesAllowed());

        $this->containment->enableGitHubWrites();
        $this->assertTrue($this->containment->isGitHubWritesAllowed());
    }
}
