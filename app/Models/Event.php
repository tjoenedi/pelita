<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property Carbon|null $date
 */
class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_type_id',
        'name',
        'description',
        'date',
        'all_day',
        'start_time',
        'end_time',
        'organization_id',
        'is_active',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'all_day' => 'boolean',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function eventPositions(): HasMany
    {
        return $this->hasMany(EventPosition::class);
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'event_position')
            ->withTimestamps();
    }

    public function positionSchedules(): HasMany
    {
        return $this->hasMany(EventPositionMember::class);
    }

    public function getAssignedPositionsWithMembers(): Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, EventPosition> $eventPositions */
        $eventPositions = $this->eventPositions()
            ->with(['position', 'schedules.member'])
            ->get();

        return $eventPositions->map(function (EventPosition $eventPosition) {
            return [
                'position' => $eventPosition->position,
                'member' => $eventPosition->getAssignedMember(),
            ];
        });
    }
}
