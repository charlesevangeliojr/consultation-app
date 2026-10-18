<?php

namespace App\Support;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountActivity
{
    public static function deactivateDormantStudents(?User $user = null): int
    {
        $cutoff = now()->subYears(2);

        return Student::query()->where('status', 'active')
            ->when($user, fn ($query) => $query->where('user_id', $user->id))
            ->whereHas('user', fn ($query) => $query
                ->where('inactivity_reference_at', '<=', $cutoff)
                ->where(fn ($activity) => $activity->whereNull('last_seen_at')->orWhere('last_seen_at', '<=', $cutoff))
                ->whereHas('role', fn ($role) => $role->whereRaw('LOWER(name) LIKE ?', ['%student%'])))
            ->update(['status' => 'inactive']);
    }

    public static function setStudentStatus(User $user, string $status): void
    {
        DB::transaction(function () use ($user, $status) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $student = $lockedUser->student()->firstOrCreate();
            if ($status === 'active' && $student->status !== 'active') {
                $lockedUser->inactivity_reference_at = now();
                $lockedUser->save();
            }
            $student->update(['status' => $status]);
        });
    }
}
