<?php

namespace Tests\Unit;

use App\Domain\Scans\LocalIsolatedRunner;
use Tests\TestCase;

final class LocalIsolatedRunnerTest extends TestCase
{
    public function test_runner_executes_command_and_captures_output(): void
    {
        $runner = new LocalIsolatedRunner(defaultTimeoutSeconds: 10);
        $result = $runner->run(
            command: ['node', '-e', 'console.log("runner test ok")'],
            workingDirectory: sys_get_temp_dir(),
        );

        $this->assertSame(0, $result['exit_code']);
        $this->assertStringContainsString('runner test ok', $result['output']);
        $this->assertFalse($result['timed_out']);
    }

    public function test_runner_enforces_bounded_timeout(): void
    {
        $runner = new LocalIsolatedRunner(defaultTimeoutSeconds: 1);
        $result = $runner->run(
            command: ['node', '-e', 'setTimeout(() => {}, 5000)'],
            workingDirectory: sys_get_temp_dir(),
            timeout: 1,
        );

        $this->assertTrue($result['timed_out']);
        $this->assertSame(124, $result['exit_code']);
        $this->assertStringContainsString('timed out', strtolower($result['output']));
    }
}
