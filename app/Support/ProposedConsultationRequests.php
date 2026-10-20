<?php

namespace App\Support;

use App\Models\ConsultationAppointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProposedConsultationRequests
{
    private static function pending(?User $user = null): Builder
    {
        $query = ConsultationAppointment::where('is_proposed', true)->where('status', 'pending');
        if ($user?->isTeacher()) $query->where('teacher_id', $user->id);
        elseif ($user?->isStudent()) $query->where('student_id', $user->id);
        return $query;
    }

    public static function reconcile(?User $user = null): int
    {
        $count = 0;
        // Use the same teacher lock as acceptance, and separate reads/writes for
        // portability to MySQL (which restricts updates with self-subqueries).
        static::pending($user)->select(['id', 'teacher_id'])->chunkById(100, function ($requests) use (&$count) {
            foreach ($requests as $candidate) {
                $count += DB::transaction(function () use ($candidate) {
                    User::whereKey($candidate->teacher_id)->lockForUpdate()->firstOrFail();
                    $booking = static::pending()->whereKey($candidate->id)->lockForUpdate()->first();
                    if (! $booking) return 0;
                    $reason = $booking->starts_at->lte(now()) ? 'expired' : null;
                    if (! $reason && ConsultationAppointment::where('teacher_id', $booking->teacher_id)->whereIn('status', ['confirmed', 'completed'])
                        ->where('starts_at', '<', $booking->ends_at)->where('ends_at', '>', $booking->starts_at)->exists()) {
                        $reason = 'time_taken';
                    }
                    if (! $reason) return 0;
                    $booking->update(['status' => 'rejected', 'rejection_reason' => $reason]);
                    return 1;
                });
            }
        });
        return $count;
    }

    // Called inside the same teacher-locked transaction that accepts a booking.
    public static function rejectOverlapping(ConsultationAppointment $accepted): int
    {
        return static::pending()->where('teacher_id', $accepted->teacher_id)->where('id', '!=', $accepted->id)
            ->where('starts_at', '<', $accepted->ends_at)->where('ends_at', '>', $accepted->starts_at)
            ->get()->each(fn ($booking) => $booking->update(['status' => 'rejected', 'rejection_reason' => 'time_taken']))->count();
    }
}
