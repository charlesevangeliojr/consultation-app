<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['endpoint', 'public_key', 'auth_token', 'endpoint_hash'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'endpoint' => 'encrypted', 'public_key' => 'encrypted', 'auth_token' => 'encrypted'];
    }
}
