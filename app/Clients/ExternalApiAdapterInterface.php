<?php

namespace App\Clients;

use App\Models\DailyPrice;
use Carbon\Carbon;

interface ExternalApiAdapterInterface
{
    /** Get the current BTC price in the system's default currency */
    public function getCurrentPrice(array $options = []): float;
    /** Get the current BTC price and stats in the system's default currency */
    public function getCurrentPriceStats(array $options = []): array;
    /** Get DailyPrice for the current BTC price */
    public function getCurrentDailyPrice(): DailyPrice;
    /** Get price [$date => $price] for the given date interval */
    public function getDailyPriceInterval(Carbon $startDate, Carbon $endDate): array;
    /** Get the closing BTC price in USD for all the given days */
    public function getBtcPriceByDays(array $days): array;
    /** Get the external API name of the adapter instance */
    public function getClientName(): string;
    /** Get the database ID of the adapter instace */
    public function getDataSourceId(): int;
}
