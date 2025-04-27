<?php

use App\Filament\Pages\PerformancePage;
use App\Http\Controllers\BtcRpcController;
use App\Http\Controllers\SandboxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::redirect('/', '/admin');

Route::redirect('/business-card', '/admin');

Route::get('/sandbox', [SandboxController::class, 'index']);

Route::get('/admin/user-signal-score/{id}', PerformancePage::class)
    ->middleware('auth')
    ->name('user-signal-score-id');

Route::get('/lightning', function () {
    throw new \BadMethodCallException('Not implemented');
    $service = new \App\Services\LightningService();
    $service->setHost('192.168.1.238:8080');
    $service->loadMacaroon(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'admin.macaroon');

    $path = $_GET['path'] ?? 'getinfo';
    return view('welcome');
});


/**
 * @see https://developer.bitcoin.org/reference/rpc/index.html
 */
if (config('app.env') === 'local' && auth()->user() && auth()->user()->role_id === 3) {
    Route::controller(BtcRpcController::class)->prefix('rpc')->group(function () {

        Route::get('/help', 'help');

        Route::get('/command/{command}', 'runCommand')->name('rpc-command.run');
        Route::get('/command/{command}/docs', 'commandDocs')->name('rpc-command.docs');

        Route::get('/mix', function () {

            /*$block = bitcoind()->getBlock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');
        dd(
            $block('hash')->get(),     // 000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f
            $block['height'],          // 0 (array access)
            $block->get('tx.0'),       // 4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b
            $block->count('tx'),       // 1
            $block->has('version'),    // key must exist and CAN NOT be null
            $block->exists('version'), // key must exist and CAN be null
            $block->contains(0),       // check if response contains value
            $block->values(),          // array of values
            $block->keys(),            // array of keys
            $block->random(1, 'tx'),   // random block txid
            $block('tx')->random(2),   // two random block txid's
            $block('tx')->first(),     // txid of first transaction
            $block('tx')->last(),      // txid of last transaction
        );*/
            dd(bitcoind()->getrpcinfo()->get());
            $balance = bitcoind()
                ->wallet('carteira2')
                //->getwalletinfo();
                ->gettransaction();
            dd($balance->get());
            // $hash = bitcoind()->getBestBlockHash();
            // dd($hash->get());

            //$hash = '000000000001caba23d5a17d5941f0c451c4ac221cbaa6c60f27502f53f87f68';
            //$block = bitcoind()->getBlock($hash);

            /** @var \Denpa\Bitcoin\Responses\LaravelResponse $response */
            // $response = bitcoind()->getbestblockhash();

            $response = bitcoind()->createwallet('carteira3');
            // ~/umbrel/app-data/bitcoin/data/bitcoin
            dd($response->result());
        });
    });
}
