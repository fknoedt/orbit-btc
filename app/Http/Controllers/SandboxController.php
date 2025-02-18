<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class SandboxController
{
    public function index(): void
    {
        if (config('app.env') !== 'local') {
            throw new \BadMethodCallException('Sandbox needs to be run on the local environment');
        }

        /**
         * create card
         * create tables
         * create Console Command with argument for raw-data file population
         * command should parse json and insert data
         * create card for another command for updating table through API
         */
        die("see TODO");

        $data = json_decode($json,true);

        foreach ($data['result']['data'] as $day) {
            $dateTime = Carbon::createFromTimestamp($day[0] / 1000);
            $reservesUsd = $day[1];
            echo $dateTime->format('Y-m-d H:i:s') . ' -> ' . $reservesUsd . '<br/>';
        }
    }
}
