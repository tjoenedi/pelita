<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property bool $reminder_enabled
 * @property int|null $email_template_id
 * @property int|null $sms_template_id
 */
class EventType extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'reminder_enabled' => 'boolean',
            'reminder_schedules' => 'array',
        ];
    }

    protected $fillable = [
        'name',
        'description',
        'organization_id',
        'reminder_enabled',
        'reminder_schedules',
        'email_template_id',
        'sms_template_id',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'event_type_positions')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function communicationTemplates(): HasMany
    {
        return $this->hasMany(CommunicationTemplate::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'email_template_id');
    }

    public function smsTemplate(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'sms_template_id');
    }
}
