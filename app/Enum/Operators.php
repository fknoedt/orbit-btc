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
            self::PLUS => 'arrow-up',
            self::MINUS => 'arrow-down',
            self::PLUS_MINUS => 'arrows-up-down',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::PLUS => '#A7F3D0', // Light green (pistachio)
            self::MINUS => '#FCA5A5', // Light red
            self::PLUS_MINUS => '#FDE68A', // Light yellow
        };
    }

    public function getLabel(): ?string
    {
        return $this->value;
    }
}
