<?php

namespace App\Enums;

enum ReminderStatus: string
{
    case Scheduled = 'scheduled';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
