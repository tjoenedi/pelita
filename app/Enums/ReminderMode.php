<?php

namespace App\Enums;

enum ReminderMode: string
{
    case Auto = 'auto';
    case Manual = 'manual';
    case Disabled = 'disabled';
}
