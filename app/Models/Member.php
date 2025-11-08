<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $first_name
 * @property string $last_name
 * @property int $id
 */
class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'address',
        'city',
        'state_province',
        'zip',
        'country',
        'birth_date',
        'birth_place',
        'gender',
        'baptism_date',
        'marital_status',
        'email',
        'profile_picture',
        'organization_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'baptism_date' => 'date',
    ];

    public function communicationPreferences()
    {
        return $this->hasMany(MemberCommunicationPreference::class);
    }

    public function reminderLogs()
    {
        return $this->hasMany(ReminderLog::class);
    }
}
