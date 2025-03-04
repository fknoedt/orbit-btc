<?php

namespace App\Enum;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Operators: string implements HasIcon, HasLabel, HasColor
{
    case PLUS = '+';
    case MINUS = '-';
    case PLUS_MINUS = '+-';

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PLUS => 'heroicon-o-plus',
            self::MINUS => 'heroicon-o-minus',
            self::PLUS_MINUS => 'heroicon-o-arrows-up-down',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::PLUS => 'green',
            self::MINUS => 'red',
            self::PLUS_MINUS => 'orange',
        };
    }

    public function getLabel(): ?string
    {
        return $this->value;
    }
}
