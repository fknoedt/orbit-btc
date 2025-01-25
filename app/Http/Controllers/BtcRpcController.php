<?php

namespace App\Http\Controllers;

use App\Services\BtcRpcService;
use Illuminate\Support\Facades\Redirect;

class BtcRpcController extends Controller
{
    public function help()
    {
        return view(
            'livewire.list-rpc-commands',
            [
                'commands' => (new BtcRpcService())->getCommands(),
            ]
        );
    }

    public function runCommand(string $command): void
    {
        $commandsList = (new BtcRpcService())->getCommands();

        if (! in_array($command, $commandsList)) {
            throw new \InvalidArgumentException("Invalid Command `{$command}`");
        }

        dd(bitcoind()->$command()->get());
    }

    public function commandDocs(string $command)
    {
        return Redirect::to("https://developer.bitcoin.org/reference/rpc/{$command}.html");
    }
}

