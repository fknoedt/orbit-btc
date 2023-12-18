<?php

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

Route::get('/', function () {
    dd(Route::getRoutes());
});

Route::get('/lightning', function () {
    $service = new \App\Services\LightningService();
    $service->setHost('192.168.0.102:8080');
    $service->loadMacaroon(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'admin.macaroon');

    $path = $_GET['path'] ?? 'getinfo';
    dd($service->request($path));
    return view('welcome');
});


Route::get('/rpc', function () {
    $hash = '000000000001caba23d5a17d5941f0c451c4ac221cbaa6c60f27502f53f87f68';
    $block = bitcoind()->getBlock($hash);
    dd($block->get());
});

/**
lnd
U2`he0
addressreadwrite
inforeadwrite
invoicesreadwrite!
macaroogeneratereadwrite
messagereadwrite
offchainreadwrite
onchainreadwrite
peersreadwrite
signegenerateread &%B,cUX
 */
