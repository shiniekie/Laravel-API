<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['site', 'username', 'password', 'image', 'user_id'];

    protected $hidden = ['user']; // 🔥 PREVENT LOOP

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
