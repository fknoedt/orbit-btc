<?php

namespace App\Enum;

use Filament\Support\Contracts\HasLabel;

enum TimeHorizon: string implements HasLabel
{
    case ONE = '1';
    case THREE = '3';
    case FIVE = '5';
    case TEN = '10';
    case FIFTEEN = '15';
    case THIRTY = '30';

    public function getLabel(): ?string
    {
        return $this->value;
    }
}
