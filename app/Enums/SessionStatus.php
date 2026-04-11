<?php

namespace App\Enums;
// app/Enums/SessionStatus.php

enum SessionStatus: string
{
    case Planned = 'planned';
    case Played  = 'played';
    case Skipped = 'skipped';

    public function label(): string
    {
        return __("enums.session_status.{$this->value}");
    }
}
