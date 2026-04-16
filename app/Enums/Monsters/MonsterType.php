<?php
namespace App\Enums\Monsters;

enum MonsterType: string
{
    case Aberration = 'aberration';
    case Beast = 'beast';
    case Celestial = 'celestial';
    case Construct = 'construct';
    case Dragon = 'dragon';
    case Elemental = 'elemental';
    case Fey = 'fey';
    case Fiend = 'fiend';
    case Giant = 'giant';
    case Humanoid = 'humanoid';
    case Monstrosity = 'monstrosity';
    case Ooze = 'ooze';
    case Plant = 'plant';
    case Undead = 'undead';
    case Unknown = 'unknown';

    public function label(): string
    {
        return __("enums.monster.type.{$this->value}");
    }

}
