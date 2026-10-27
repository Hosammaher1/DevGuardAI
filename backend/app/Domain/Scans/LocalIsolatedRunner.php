<?php

namespace App\Domain\Scans;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class LocalIsolatedRunner
{
    public function __construct(
        private readonly int $defaultTimeoutSeconds = 60,
    ) {}

    /**
     * Execute a scan command in an isolated child process with timeout and resource controls.
     *
     * @param array<int, string> $command
     * @param string $workingDirectory
     * @param array<string, string> $environment
     * @param int|null $timeout
     * @return array{exit_code: int, output: string, timed_out: bool}
     */
    public function run(
        array $command,
        string $workingDirectory,
        array $environment = [],
        ?int $timeout = null
    ): array {
        $configuredTimeout = function_exists('config') ? config('devguard-runner.timeout_seconds') : null;
        $effectiveTimeout = $timeout ?? ($configuredTimeout ? (int) $configuredTimeout : $this->defaultTimeoutSeconds);

        // Sanitize environment variables: allow safe runtime vars only
        $safeEnv = [
            'PATH' => getenv('PATH') ?: '',
            'NODE_PATH' => getenv('NODE_PATH') ?: '',
            'SYSTEMROOT' => getenv('SYSTEMROOT') ?: '',
            'WINDIR' => getenv('WINDIR') ?: '',
            'TEMP' => sys_get_temp_dir(),
            'TMP' => sys_get_temp_dir(),
            'CI' => '1',
            'DEVGUARD_EXECUTION_MODE' => 'isolated-local',
        ];

        foreach ($environment as $key => $value) {
            $safeEnv[$key] = $value;
        }

        $process = new Process($command, $workingDirectory, $safeEnv, null, (float) $effectiveTimeout);

        try {
            $process->run();

            return [
                'exit_code' => $process->getExitCode() ?? 1,
                'output' => $process->getOutput() . ($process->getErrorOutput() ? "\n" . $process->getErrorOutput() : ''),
                'timed_out' => false,
            ];
        } catch (ProcessTimedOutException $e) {
            try {
                Log::warning("Isolated runner process timed out after {$effectiveTimeout}s: " . implode(' ', $command));
            } catch (\Throwable) {}

            return [
                'exit_code' => 124,
                'output' => "Execution timed out after {$effectiveTimeout} seconds.\n" . $e->getMessage(),
                'timed_out' => true,
            ];
        } catch (\Throwable $e) {
            try {
                Log::error("Isolated runner process error: {$e->getMessage()}");
            } catch (\Throwable) {}

            return [
                'exit_code' => 1,
                'output' => "Runner failure: {$e->getMessage()}",
                'timed_out' => false,
            ];
        }
    }
}
