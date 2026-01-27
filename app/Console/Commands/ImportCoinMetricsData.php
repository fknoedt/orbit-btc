<?php

namespace App\Console\Commands;

use App\Clients\CoinMetricsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportCoinMetricsData extends Command
{
    protected $signature = 'btc:ingest-coinmetrics-data';
    protected $description = 'Fetch and import latest BTC metrics from CoinMetrics GitHub CSV into daily_prices table';

    public function handle(): int
    {
        $url = 'https://raw.githubusercontent.com/coinmetrics/data/master/csv/btc.csv';
        $response = Http::get($url);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch CSV from ' . $url . ': ' . $response->body());
        }

        $csvData = $response->body();
        $lines = explode(PHP_EOL, $csvData);
        $headers = str_getcsv(array_shift($lines)); // Get headers

        // Map headers to DB columns (excluding irrelevant ones)
        $headerMap = [
            'time' => 'date',
            ...CoinMetricsClient::METRIC_TO_COLUMN_NAME,
        ];

        $totalLines = count($lines);
        $progressBar = $this->output->createProgressBar($totalLines);
        $progressBar->start();

        $updated = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $progressBar->advance();

            if (empty($line)) {
                $skipped++;
                continue;
            }

            $row = str_getcsv($line);
            $data = array_combine($headers, $row);

            $date = $data['time'] ?? null;
            if (!$date) {
                $skipped++;
                continue;
            }

            // Prepare update data
            $updateData = [];
            foreach ($headerMap as $csvKey => $dbCol) {
                if ($csvKey === 'time') {
                    continue; // Skip date
                }
                $value = $data[$csvKey] ?? null;
                $updateData[$dbCol] = $value ? (float) $value : null; // Cast to float
            }

            // Update existing rows only (assuming dates exist)
            $affected = DB::table('daily_prices')
                ->where('date', $date)
                ->update($updateData);

            if ($affected > 0) {
                $updated++;
            } else {
                $skipped++;
            }
        }

        $progressBar->finish();

        $this->info(PHP_EOL . "Updated {$updated} records. Skipped {$skipped} rows (invalid or no matching date).");

        return 0;
    }
}
