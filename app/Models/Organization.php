<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property int|null $reminder_days_before
 * @property string|null $reminder_time
 * @property string|null $timezone
 */
class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'reminder_enabled',
        'reminder_days_before',
        'reminder_time',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'reminder_enabled' => 'boolean',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function communicationTemplates(): HasMany
    {
        return $this->hasMany(CommunicationTemplate::class);
    }

    public function memberCommunicationPreferences(): HasMany
    {
        return $this->hasMany(MemberCommunicationPreference::class);
    }
}
