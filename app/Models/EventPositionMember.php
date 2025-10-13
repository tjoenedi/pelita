<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Member $member
 */
class EventPositionMember extends Model
{
    use HasFactory;

    protected $table = 'event_position_member';

    protected $fillable = [
        'event_position_id',
        'member_id',
        'event_id',
    ];

    public function eventPosition(): BelongsTo
    {
        return $this->belongsTo(EventPosition::class, 'event_position_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
