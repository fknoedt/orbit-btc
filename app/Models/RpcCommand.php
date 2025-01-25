<?php

namespace App\Models;

use App\Services\BtcRpcService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Sushi\Sushi;

/**
 * This is not an Eloquent model and is not persisted to the database; commands are read in real time through BTC's API
 */
class RpcCommand extends Model
{
    use Sushi;

    public function getRows(): array
    {
        return (new BtcRpcService())->getCommandsWithArguments();
    }
}
