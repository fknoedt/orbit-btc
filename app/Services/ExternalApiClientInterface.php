<?php

namespace App\Services;

use Carbon\Carbon;

interface ExternalApiClientInterface
{
    public function getCurrentBtcPrice(array $options = []): array;
    public function getBtcPriceInterval(Carbon $startDate, Carbon $endDate): array;
    public function getBtcPriceByDays(array $days): array;
}
