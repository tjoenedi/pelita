<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
