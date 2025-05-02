<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;

class SandboxController
{
    public function index(): void
    {
        if (config('app.env') !== 'local' || auth()->user()->role_id < config('data.role_id.super_admin')) {
            throw new AuthorizationException('Access denied and reported');
        }

        // playground -- you probably don't need to commit anything in this file
    }
}
