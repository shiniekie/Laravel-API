<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'image'
    ];

    // ✅ 
    protected $hidden = [
    'password',
    'remember_token',
    'tokens'
];

    //
    protected $with = [];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 🔥 PREVENT LOOP / HEAVY RESPONSE
    public function accounts()
    {
        return $this->hasMany(\App\Models\Account::class)
                    ->select(['id', 'site', 'username', 'user_id']); 
    }
}
