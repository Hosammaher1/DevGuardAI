<?php

namespace App\Domain\Runners;

enum RunnerOperation: string
{
    case Detect = 'detect';
    case Plan = 'plan';
    case Validate = 'validate';
    case Execute = 'execute';
    case Collect = 'collect';
    case Cleanup = 'cleanup';
}
