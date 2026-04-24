<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

class PersonalAccessToken extends SanctumToken
{
    protected $hidden = [
        'tokenable'
    ];

    public function tokenable()
    {
        // ✅ prevent recursive loading
        return $this->morphTo()->withoutGlobalScopes();
    }
}