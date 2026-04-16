<?php

namespace App\Enums\Monsters;

enum MonsterSize: string
{
    case Tiny = 'tiny';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Huge = 'huge';
    case Gargantuan = 'gargantuan';

    public function label(): string
    {
        return __("enums.monster.size.{$this->value}");
    }
}
