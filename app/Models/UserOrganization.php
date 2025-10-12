<?php

namespace App\Models;

use Database\Factories\UserOrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserOrganization extends Pivot
{
    /** @use HasFactory<UserOrganizationFactory> */
    use HasFactory;

    protected $table = 'organization_user';
}
