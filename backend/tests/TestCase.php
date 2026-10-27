<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPrivateArtifactsDisk();
    }

    protected function setupPrivateArtifactsDisk(): void
    {
        $tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'devguard-test-artifacts';
        if (! is_dir($tempRoot)) {
            @mkdir($tempRoot, 0777, true);
        }

        Storage::set(
            'private-artifacts',
            Storage::createLocalDriver([
                'root' => $tempRoot,
            ])
        );
    }
}
