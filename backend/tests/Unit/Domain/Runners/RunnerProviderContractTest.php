<?php

namespace Tests\Unit\Domain\Runners;

use App\Domain\Runners\RunnerOperation;
use App\Domain\Runners\RunnerOperationOutcome;
use App\Domain\Runners\RunnerOperationRequest;
use App\Domain\Runners\RunnerOperationResult;
use App\Domain\Runners\RunnerProviderContract;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RunnerProviderContractTest extends TestCase
{
    public function test_request_is_scoped_and_uses_a_closed_operation_vocabulary(): void
    {
        $request = new RunnerOperationRequest(
            workspaceId: 'workspace-test-1',
            jobId: 'job-test-7',
            capability: 'web-test',
            operation: RunnerOperation::Validate,
            requestId: 'request-test-2',
            budgetReference: 'budget-test-1',
        );

        $this->assertSame('workspace-test-1', $request->workspaceId);
        $this->assertSame(RunnerOperation::Validate, $request->operation);
        $this->assertObjectNotHasProperty('command', $request);
        $this->assertObjectNotHasProperty('payload', $request);
    }

    public function test_request_rejects_missing_or_unsafe_scope_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RunnerOperationRequest(
            workspaceId: "workspace\nother-tenant",
            jobId: 'job-test-7',
            capability: 'web-test',
            operation: RunnerOperation::Execute,
            requestId: 'request-test-2',
            budgetReference: 'budget-test-1',
        );
    }

    public function test_contract_can_be_exercised_with_a_test_local_mock_only(): void
    {
        $mock = new class implements RunnerProviderContract
        {
            public array $requests = [];

            public function perform(RunnerOperationRequest $request): RunnerOperationResult
            {
                $this->requests[] = $request;

                return new RunnerOperationResult($request->requestId, RunnerOperationOutcome::Accepted);
            }
        };
        $request = new RunnerOperationRequest(
            'workspace-test-1', 'job-test-7', 'api-test', RunnerOperation::Plan,
            'request-test-2', 'budget-test-1',
        );

        $result = $mock->perform($request);

        $this->assertSame([$request], $mock->requests);
        $this->assertSame('request-test-2', $result->requestId);
        $this->assertSame(RunnerOperationOutcome::Accepted, $result->outcome);
    }

    public function test_result_rejects_invalid_correlation_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RunnerOperationResult("request\nother", RunnerOperationOutcome::Completed);
    }

    public function test_contract_types_do_not_assert_provider_or_isolation_assurance(): void
    {
        $this->assertFalse(method_exists(RunnerOperationResult::class, 'isProviderEligible'));
        $this->assertFalse(method_exists(RunnerOperationResult::class, 'isIsolated'));
    }
}
