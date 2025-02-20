<?php

namespace App\Http\Controllers;

class SandboxController
{
    public function index(): void
    {
        if (config('app.env') !== 'local') {
            throw new \BadMethodCallException('Sandbox needs to be run on the local environment');
        }

        // playground -- you probably don't need to commit anything in this file
    }
}
