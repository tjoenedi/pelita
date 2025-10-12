<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventScheduleItem extends Model
{
    /** @use HasFactory<\Database\Factories\EventScheduleItemFactory> */
    use HasFactory;

    protected $table = 'event_schedule_items';
}
