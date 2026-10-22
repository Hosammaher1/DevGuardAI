<?php

use App\Domain\Identity\MembershipService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$jobPath = $argv[1] ?? null;
if (! is_string($jobPath) || ! is_file($jobPath)) {
    exit(2);
}

$job = json_decode(file_get_contents($jobPath), true, flags: JSON_THROW_ON_ERROR);
if (! is_array($job)) {
    exit(3);
}

putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

file_put_contents($job['ready_path'], 'ready', LOCK_EX);
$deadline = microtime(true) + 10;
while (! is_file($job['go_path'])) {
    if (microtime(true) >= $deadline) {
        file_put_contents($job['result_path'], json_encode(['result' => 'barrier_timeout'], JSON_THROW_ON_ERROR), LOCK_EX);
        exit(4);
    }
    usleep(10_000);
}

try {
    $service = $app->make(MembershipService::class);
    $at = new DateTimeImmutable($job['at']);

    if ($job['operation'] === 'accept_invitation') {
        $service->acceptInvitation($job['token'], $job['user_id'], $at);
        $result = 'accepted';
    } elseif ($job['operation'] === 'accept_ownership_transfer') {
        $service->acceptOwnershipTransfer($job['transfer_id'], $job['user_id'], $at);
        $result = 'accepted';
    } else {
        $result = 'unsupported_operation';
    }
} catch (DomainException $exception) {
    $result = $exception->getMessage();
} catch (Throwable) {
    $result = 'unexpected_worker_failure';
}

$temporaryResult = $job['result_path'].'.tmp.'.getmypid();
file_put_contents($temporaryResult, json_encode(['result' => $result], JSON_THROW_ON_ERROR), LOCK_EX);
rename($temporaryResult, $job['result_path']);

exit($result === 'unexpected_worker_failure' ? 5 : 0);
