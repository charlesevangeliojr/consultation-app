<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->isTeacher() || $request->routeIs('consultations.teacher-profile.*', 'consultations.logout', 'consultations.tour.complete')) {
            return $next($request);
        }
        // Never prevent a teacher from freeing a slot or closing an existing booking.
        if ($request->routeIs('consultations.availability.cancel') ||
            ($request->routeIs('consultations.appointments.update') && in_array($request->input('status'), ['rejected', 'cancelled', 'completed'], true))) {
            return $next($request);
        }
        $profile = $user->teacher()->firstOrCreate();
        if (! $profile->hasCompleteProfile()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Complete your teacher profile before using the workspace.',
                    'missing_fields' => $profile->missingProfileFields(),
                    'profile_url' => route('consultations.teacher-profile.edit'),
                ], 403);
            }

            return redirect()->route('consultations.teacher-profile.edit')
                ->with('profile_required', 'Complete your teacher profile to unlock your calendar, requests, availability, and settings.');
        }

        return $next($request);
    }
}
