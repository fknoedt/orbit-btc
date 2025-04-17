<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->string('y_title')->nullable()->after('widget_class');
            $table->string('color')->nullable()->after('y_title')->comment('Hexadecimal color code (e.g., #RRGGBB)');
            $table->text('description')->nullable()->after('color');
        });

        // Update existing records with values from the hard-coded array
        DB::table('metrics')->where('id', 1)->update([
            'y_title' => 'Price (USD)',
            'color' => '#FF9800',
            'description' => 'BTC Price, or Closing Price, is the current market value of Bitcoin in USD at the end of a trading period. It’s the most direct indicator of market sentiment—rising prices often reflect growing investor optimism and increased demand, potentially leading to further price increases. Conversely, falling prices may signal bearish conditions, such as profit-taking or reduced interest. Monitoring this metric helps you spot trends and predict short-term price movements based on historical patterns.',
        ]);

        DB::table('metrics')->where('id', 2)->update([
            'y_title' => 'Volume (USD)',
            'color' => '#2196F3',
            'description' => 'Total Volume Traded represents the total value of Bitcoin exchanged in USD over a specific period. High volume accompanying price increases can confirm a strong bullish trend, as it suggests widespread participation and conviction among traders. Low volume during price changes might indicate weak momentum or manipulation, often preceding reversals. This metric is useful for validating price trends and identifying potential breakouts or breakdowns in Bitcoin’s market.',
        ]);

        DB::table('metrics')->where('id', 3)->update([
            'y_title' => 'Market Cap (USD)',
            'color' => '#4CAF50',
            'description' => 'Market Cap is the total value of Bitcoin in circulation, calculated as price multiplied by the circulating supply. A rising market cap often signals increased demand or new investor interest, which can drive price growth. A declining cap may indicate selling pressure or a reduction in circulating supply due to lost coins, potentially leading to price drops. This metric provides a broad view of Bitcoin’s market size and its correlation with price over time.',
        ]);

        DB::table('metrics')->where('id', 4)->update([
            'y_title' => 'Fee (BTC)',
            'color' => '#9C27B0',
            'description' => 'Average BTC Fee is the average transaction fee paid on the Bitcoin network in BTC. High fees during price rises can indicate network congestion and strong bullish activity, as more users transact during bullish markets. Low fees might suggest reduced network usage, often correlating with price stagnation or declines. This metric helps gauge network health and can signal shifts in market activity that might influence Bitcoin’s price.',
        ]);

        DB::table('metrics')->where('id', 5)->update([
            'y_title' => 'Reserve (BTC)',
            'color' => '#FF5722',
            'description' => 'Total Exchanges Reserve tracks the amount of Bitcoin held on exchange wallets. A decreasing reserve might indicate that investors are moving BTC to personal wallets (a bullish signal), suggesting they expect price increases and are holding long-term. An increasing reserve could signal selling pressure or preparation for sales, often a bearish indicator. This metric is key for understanding market sentiment and predicting potential price movements based on exchange activity.',
        ]);

        DB::table('metrics')->where('id', 6)->update([
            'y_title' => 'Index',
            'color' => '#607D8B',
            'description' => 'The Fear & Greed Index measures market sentiment on a scale from extreme fear to extreme greed, based on factors like volatility, trading volume, and social media. High greed levels often precede price peaks as investors become overconfident, while extreme fear can signal buying opportunities, as prices may rebound after panic selling. This metric is a psychological tool to assess whether Bitcoin’s price is driven by emotion rather than fundamentals.',
        ]);

        DB::table('metrics')->where('id', 7)->update([
            'y_title' => 'Multiple',
            'color' => '#E91E63',
            'description' => 'Mayer Multiple compares Bitcoin’s current price to its 200-day moving average, offering insight into overvaluation or undervaluation. High values (e.g., above 2.4) suggest the price is overstretched, often leading to corrections or drops, while low values (e.g., below 1) may indicate undervaluation and potential growth. This metric helps identify long-term trends and whether Bitcoin’s price is at a sustainable level.',
        ]);

        DB::table('metrics')->where('id', 8)->update([
            'y_title' => 'Hashrate',
            'color' => '#A067E9',
            'description' => 'Average Hashrate measures the total computational power securing the Bitcoin network. A rising hashrate indicates more miners joining, which can support price stability by enhancing security and decentralization, often correlating with price increases. A drop in hashrate might signal miner capitulation or reduced profitability, potentially leading to price declines. This metric reflects the network’s health and its indirect influence on price.',
        ]);

        DB::table('metrics')->where('id', 9)->update([
            'y_title' => 'Difficulty',
            'color' => '#E9E454',
            'description' => 'Difficulty reflects how hard it is to mine a Bitcoin block, adjusting every 2,016 blocks based on hashrate. Increasing difficulty often aligns with price rises, as more miners invest during bullish markets, boosting network security. Decreases in difficulty might signal a bearish market, as miners exit due to low profitability, potentially dragging prices down. This metric is a long-term indicator of mining activity and price correlation.',
        ]);
    }

    public function down(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->dropColumn(['y_title', 'color', 'description']);
        });
    }
};
