<?php

namespace App\Http\Controllers;

use App\Models\ConsultationAppointment;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        abort_unless($user->isStudent(), 403);

        $month = now()->startOfMonth();
        $monthAppointments = ConsultationAppointment::query()
            ->where('student_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereBetween('starts_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get(['starts_at']);

        $appointments = ConsultationAppointment::query()
            ->where('student_id', $user->id)
            ->where('starts_at', '>=', now())
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->with('teacher')
            ->orderBy('starts_at')
            ->limit(6)
            ->get();

        $appointmentDates = $monthAppointments
            ->map(fn (ConsultationAppointment $appointment) => $appointment->starts_at->toDateString())
            ->unique()
            ->values();
        $history = ConsultationAppointment::where('student_id', $user->id)
            ->where(function ($query) {
                $query->whereIn('status', ['cancelled', 'rejected', 'completed'])->orWhere('starts_at', '<', now());
            })->with(['teacher', 'availability'])->orderByDesc('updated_at')->paginate(10);

        return view('student.dashboard', compact('appointments', 'appointmentDates', 'month', 'history'));
    }
}
