<?php

namespace App\Support;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Str;

class PortalNavigationBadges
{
    public static function forUser(User $user): array
    {
        $isTeacher = $user->isTeacher();
        if (! $isTeacher && ! $user->isStudent()) return [];
        if (! $isTeacher) {
            $count = ConsultationAppointment::where('student_id', $user->id)->needsStudentAttention()->count();
            return ['users' => ['badge' => $count, 'badgeLabel' => $count.' unfinished consultations or unseen rejections']];
        }

        $counts = ConsultationAppointment::where($isTeacher ? 'teacher_id' : 'student_id', $user->id)
            ->where('starts_at', '>', now())->whereIn('status', ['pending', 'confirmed'])
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $pending = (int) ($counts['pending'] ?? 0);
        $confirmed = (int) ($counts['confirmed'] ?? 0);
        $requestsLabel = Str::plural('consultation request', $pending);
        $bookingsLabel = Str::plural('booking', $pending);
        $confirmedLabel = Str::plural('consultation', $confirmed);
        $badges = [
            $isTeacher ? 'users' : 'home' => ['badge' => $pending, 'badgeLabel' => $isTeacher
                ? "$pending $requestsLabel awaiting your approval" : "$pending $bookingsLabel awaiting teacher approval"],
            'calendar' => ['badge' => $confirmed, 'badgeLabel' => "$confirmed upcoming confirmed $confirmedLabel"],
        ];

        if ($isTeacher) {
            $windows = ConsultationAvailability::where('teacher_id', $user->id)->whereNull('cancelled_at')
                ->where(function ($query) {
                    $query->where('date', '>', today()->toDateString())
                        ->orWhere(fn ($today) => $today->whereDate('date', today())->where('ends_at', '>', now()->format('H:i:s')));
                })->count();
            $profile = $user->teacher ?? (new Teacher)->setRelation('user', $user);
            $missing = count($profile->missingProfileFields());
            $badges['plus'] = ['badge' => $windows, 'badgeLabel' => $windows.' published availability '.Str::plural('window', $windows).' remaining'];
            $badges['profile'] = ['badge' => $missing, 'badgeLabel' => $missing.' required profile '.Str::plural('detail', $missing).' missing'];
        }

        return $badges;
    }
}
