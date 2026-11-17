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

    public function destination(User $user): string
    {
        $allowed = [];
        if ($user->isStudent()) {
            array_push($allowed, '/consultations', '/consultations/dashboard', '/consultations/browse', '/consultations/student/consultations', '/consultations/student/profile');
        }
        if ($user->isTeacher()) {
            array_push($allowed, '/consultations', '/consultations/teacher', '/consultations/teacher/requests', '/consultations/teacher/availability', '/consultations/teacher/settings', '/consultations/teacher/profile');
        }
        if ($user->hasAdminAccess()) {
            if ($user->canAccess('dashboard', 'view')) {
                array_push($allowed, '/admin', '/admin/consultations');
            }
            if ($user->canAccess('accounts', 'view')) {
                $allowed[] = '/admin/accounts';
            }
            if ($user->canAccess('settings', 'view')) {
                array_push($allowed, '/admin/settings', '/admin/school');
            }
        }

        $target = $this->url;
        if ($user->isTeacher() && $this->title === 'Scheduling permission update' && $target === '/consultations/teacher/profile') {
            if ($user->teacher?->consultation_approved && $user->teacher?->hasCompleteProfile()) {
                $target = '/consultations/teacher/availability';
            }
        }

        return in_array($target, $allowed, true) ? $target : '/notifications';
    }

    public function hasDestination(User $user): bool
    {
        return $this->destination($user) !== '/notifications';
    }
}
