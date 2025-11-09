<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case SMS = 'sms';
    case All = 'all';
}
