<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $member_id
 * @property int $event_id
 * @property int|null $event_type_id
 * @property NotificationChannel $channel
 * @property \Illuminate\Support\Carbon $scheduled_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property ReminderStatus $status
 * @property string|null $failure_reason
 * @property array $template_snapshot
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Member $member
 * @property-read Event $event
 * @property-read EventType|null $eventType
 */
class ReminderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'event_id',
        'event_type_id',
        'channel',
        'scheduled_at',
        'sent_at',
        'status',
        'failure_reason',
        'template_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => ReminderStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'template_snapshot' => 'array',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }
}
