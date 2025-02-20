<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $output = new ConsoleOutput();

        Schema::table('daily_prices', function (Blueprint $table) {
            $table->float('average_fee')->nullable()->comment('Fees per transaction in USD (mean)');
            $table->float('exchanges_reserve')->nullable()->comment('value in USD');
        });

        $output->writeln('Tables created');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

//curl 'https://live-api.cryptoquant.com/api/v3/charts/61adc7976bc0e955292d7316?window=DAY&from=1108789200000&to=1739944438473&limit=70000' \
//  -H 'accept: application/json, text/plain, */*' \
//  -H 'accept-language: en-US,en;q=0.9' \
//  -H 'authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJ1c2VySWQiOiI2NjQzNjIiLCJpc3MiOiJDcnlwdG9RdWFudCIsImlhdCI6MTczOTk0MjkxNiwiZXhwIjoxNzM5OTQ2NTE2fQ.e8WvWona_5TZ5oW56oZohukanMvLCOta0_A3bn0Yrqs' \
//  -H 'origin: https://cryptoquant.com' \
//  -H 'priority: u=1, i' \
//  -H 'referer: https://cryptoquant.com/' \
//  -H 'sec-ch-ua: "Not(A:Brand";v="99", "Brave";v="133", "Chromium";v="133"' \
//  -H 'sec-ch-ua-mobile: ?0' \
//  -H 'sec-ch-ua-platform: "macOS"' \
//  -H 'sec-fetch-dest: empty' \
//  -H 'sec-fetch-mode: cors' \
//  -H 'sec-fetch-site: same-site' \
//  -H 'sec-gpc: 1' \
//  -H 'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'
