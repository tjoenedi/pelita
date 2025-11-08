<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $member_id
 * @property int $organization_id
 * @property int|null $event_type_id
 * @property NotificationChannel $channel
 * @property bool $is_subscribed
 * @property \Illuminate\Support\Carbon|null $unsubscribed_at
 * @property string $unsubscribe_token
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Member $member
 * @property-read Organization $organization
 * @property-read EventType|null $eventType
 */
class MemberCommunicationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'organization_id',
        'event_type_id',
        'channel',
        'is_subscribed',
        'unsubscribed_at',
        'unsubscribe_token',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'is_subscribed' => 'boolean',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MemberCommunicationPreference $preference) {
            if (! $preference->unsubscribe_token) {
                $preference->unsubscribe_token = Str::random(64);
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }
}
