<?php

namespace App\Http\Livewire;

use App\Services\BtcRpcService;
use Livewire\Component;

class ListRpcCommands extends Component
{
    public string $search = '';

    public function render() {
        return view('livewire.list-rpc-commands', [
            'commands' => (new BtcRpcService())->help($this->search)
        ]);
    }
}
