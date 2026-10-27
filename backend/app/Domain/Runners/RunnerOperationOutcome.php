<?php

namespace App\Domain\Runners;

enum RunnerOperationOutcome: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Started = 'started';
    case Completed = 'completed';
    case Failed = 'failed';
    case Indeterminate = 'indeterminate';
}
