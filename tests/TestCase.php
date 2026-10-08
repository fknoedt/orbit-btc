<?php

namespace Tests;

use Illuminate\Foundation\Mix;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $guardableColumns = new \ReflectionProperty(\Illuminate\Database\Eloquent\Model::class, 'guardableColumns');
        $guardableColumns->setValue(null, []);
    }
}
