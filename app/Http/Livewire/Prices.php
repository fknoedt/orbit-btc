<?php

namespace App\Http\Livewire;

use App\Models\DailyPrice;
use Livewire\Component;

class Prices extends Component
{
    public $search = '';

    public function where()
    {
    }

    public function render()
    {
        $users = empty($this->search) ?
            DailyPrice::all() :
            DailyPrice::where('date', '>=', $this->search)->get();

        return view('livewire.prices', [
            'users' => $users,
        ]);
    }
}
