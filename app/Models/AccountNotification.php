<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountNotification extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'read_at' => 'datetime'];
    }

    public function destination(): string
    {
        return in_array($this->url, [
            '/consultations', '/consultations/teacher/requests', '/consultations/student/consultations',
            '/consultations/teacher/profile', '/admin', '/admin/accounts',
        ], true) ? $this->url : '/notifications';
    }
}
