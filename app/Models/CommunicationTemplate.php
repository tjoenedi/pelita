<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $event_type_id
 * @property NotificationChannel $type
 * @property string $name
 * @property string|null $subject
 * @property string $content
 * @property bool $is_default
 * @property bool $append_unsubscribe_footer
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Organization $organization
 * @property-read EventType|null $eventType
 */
class CommunicationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'event_type_id',
        'type',
        'name',
        'subject',
        'content',
        'is_default',
        'append_unsubscribe_footer',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationChannel::class,
            'is_default' => 'boolean',
            'append_unsubscribe_footer' => 'boolean',
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
}
