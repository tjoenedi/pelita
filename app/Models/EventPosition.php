<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Position $position
 */
class EventPosition extends Model
{
    use HasFactory;

    protected $table = 'event_position';

    protected $fillable = [
        'event_id',
        'position_id',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EventPositionMember::class, 'event_position_id');
    }

    public function getAssignedMember(): ?Member
    {
        /** @var EventPositionMember|null $schedule */
        $schedule = $this->schedules()->with('member')->first();

        return $schedule?->member;
    }
}
