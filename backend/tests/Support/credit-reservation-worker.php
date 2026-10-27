<?php

use App\Domain\Credits\CreditKind;
use App\Domain\Credits\CreditLedgerService;
use App\Domain\Credits\CreditReservationDenied;
use App\Domain\Credits\DevelopmentCreditSettlementService;
use App\Models\User;
use Carbon\CarbonImmutable;
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
    if (($job['action'] ?? 'reserve') === 'settle') {
        $entry = $app->make(DevelopmentCreditSettlementService::class)->settle(
            $job['reservation_id'],
            $job['scenario'],
            CarbonImmutable::parse('2026-10-22T10:05:00Z'),
        );
        $result = 'settled:'.$entry->getKey();
    } else {
        $reservation = $app->make(CreditLedgerService::class)->reserve(
            User::query()->findOrFail($job['user_id']),
            $job['workspace_id'],
            CreditKind::from($job['kind'] ?? 'scan'),
            $job['operation_id'],
            $job['idempotency_key'],
            ['source' => 'postgres-concurrency-fixture'],
            1,
            CarbonImmutable::parse('2026-10-22T10:00:00Z'),
        );
        $result = 'reserved:'.$reservation->getKey();
    }
} catch (CreditReservationDenied $exception) {
    $result = $exception->reason;
} catch (Throwable) {
    $result = 'unexpected_worker_failure';
}

$temporaryResult = $job['result_path'].'.tmp.'.getmypid();
file_put_contents($temporaryResult, json_encode(['result' => $result], JSON_THROW_ON_ERROR), LOCK_EX);
rename($temporaryResult, $job['result_path']);

exit($result === 'unexpected_worker_failure' ? 5 : 0);
