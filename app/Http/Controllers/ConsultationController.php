<?php

namespace App\Http\Controllers;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\User;
use App\Models\SiteSetting;
use App\Support\ProposedConsultationRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function login(): View|RedirectResponse
    {
        return auth()->check() ? redirect()->route('consultations.home') : view('auth.portal-login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! auth()->attempt($credentials)) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        if (! $request->user()->isTeacher() && ! $request->user()->isStudent() && ! $request->user()->hasAdminAccess()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('portal.login')->withErrors(['email' => 'This account does not have a Student, Teacher, or Administrator role.']);
        }

        return redirect()->route('consultations.home');
    }

    public function home(): View|RedirectResponse
    {
        $user = request()->user();
        if ($user->isTeacher()) return redirect()->route('consultations.teacher');
        if ($user->isStudent()) return redirect()->route('consultations.dashboard');
        return redirect()->route('admin.dashboard');
    }

    public function browse(): View
    {
        $this->requireRole('student');
        $teachers = User::query()->with(['role', 'teacher.subjects'])->whereHas('teacher', fn ($query) => $query->where('consultation_approved', true))->whereHas('role', function ($query) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%teacher%'])->orWhereRaw('LOWER(name) LIKE ?', ['%faculty%']);
        })->orderBy('name')->get();
        $selectedTeacher = request()->integer('teacher') ? $teachers->firstWhere('id', request()->integer('teacher')) : $teachers->first();
        abort_if(request()->integer('teacher') && ! $selectedTeacher, 404);
        $availability = $selectedTeacher ? ConsultationAvailability::query()->where('teacher_id', $selectedTeacher->id)->whereNull('cancelled_at')->whereDate('date', '>=', today())->with('appointments.student')->orderBy('date')->orderBy('starts_at')->get() : collect();
        $studentBookings = ConsultationAppointment::where('student_id', request()->user()->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])->where('ends_at', '>', now())
            ->orderBy('starts_at')->get(['starts_at', 'ends_at']);
        $teacherBookings = $selectedTeacher ? ConsultationAppointment::where('teacher_id', $selectedTeacher->id)
            ->reservesTeacherTime()->where('ends_at', '>', now())->get(['starts_at', 'ends_at']) : collect();

        $showStudentNames = $selectedTeacher?->teacher?->queueNamesVisible(SiteSetting::current()) ?? false;
        $monthInput = request()->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = isset($monthInput['month']) ? Carbon::createFromFormat('!Y-m', $monthInput['month'])->startOfMonth() : now()->startOfMonth();

        return view('student.browse', compact('teachers', 'selectedTeacher', 'availability', 'studentBookings', 'teacherBookings', 'showStudentNames', 'month'));
    }

    public function book(Request $request, ConsultationAvailability $availability): RedirectResponse
    {
        $this->requireRole('student');
        // Native time controls post H:i. Also accept explicit school-timezone
        // datetimes for API callers; every range is checked against the period.
        foreach (['starts_at', 'ends_at'] as $field) {
            $value = $request->input($field);
            if (is_string($value) && preg_match('/^\d{2}:\d{2}$/', $value)) {
                $request->merge([$field => $availability->date->toDateString().' '.$value]);
            }
        }
        $data = $request->validate([
            'starts_at' => ['required', 'date_format:Y-m-d H:i,Y-m-d H:i:s', 'after:now'],
            'ends_at' => ['required', 'date_format:Y-m-d H:i,Y-m-d H:i:s', 'after:starts_at'],
            'topic' => ['required', 'string', 'max:500'],
        ]);
        $start = Carbon::parse($data['starts_at']);
        $end = Carbon::parse($data['ends_at']);

        DB::transaction(function () use ($request, $availability, $start, $end, $data) {
            $teacher = User::whereKey($availability->teacher_id)->lockForUpdate()->firstOrFail();
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($teacher->isTeacher() && $teacher->teacher?->consultation_approved, 403, 'This teacher is not approved for consultations.');
            $period = ConsultationAvailability::query()->whereKey($availability->id)->lockForUpdate()->firstOrFail();
            abort_if($period->cancelled_at, 422, 'This availability has been cancelled.');
            if (! $start->isFuture()) {
                throw ValidationException::withMessages(['starts_at' => 'Choose a consultation time in the future.']);
            }
            abort_unless($period->date->isFuture() || $period->date->isToday(), 422, 'This consultation period has passed.');
            $periodStart = Carbon::parse($period->date->toDateString().' '.$period->starts_at);
            $periodEnd = Carbon::parse($period->date->toDateString().' '.$period->ends_at);
            if ($start->lt($periodStart) || $end->gt($periodEnd) || $start->second !== 0 || $end->second !== 0) {
                throw ValidationException::withMessages(['starts_at' => 'Choose a valid time within the teacher’s published availability.']);
            }
            $maximum = $teacher->teacher->max_consultation_minutes;
            if ($start->diffInMinutes($end) > $maximum) {
                throw ValidationException::withMessages(['ends_at' => "This teacher allows a maximum of {$maximum} minutes per consultation. Choose a shorter time range."]);
            }
            $conflict = ConsultationAppointment::query()->where('teacher_id', $period->teacher_id)->reservesTeacherTime()->where('starts_at', '<', $end)->where('ends_at', '>', $start)->lockForUpdate()->exists();
            if ($conflict) throw ValidationException::withMessages(['starts_at' => 'That time was just booked. Please select another available time.']);
            $studentConflict = ConsultationAppointment::where('student_id', $request->user()->id)->whereNotIn('status', ['cancelled', 'rejected'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
            if ($studentConflict) throw ValidationException::withMessages(['starts_at' => 'You already have a consultation at this time.']);

            $booking = ConsultationAppointment::create([
                'availability_id' => $period->id,
                'teacher_id' => $period->teacher_id,
                'student_id' => $request->user()->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'topic' => $data['topic'] ?? null,
                'status' => $teacher->teacher->auto_approve_bookings ? 'confirmed' : 'pending',
            ]);
            if ($booking->status === 'confirmed') ProposedConsultationRequests::rejectOverlapping($booking);
        });

        return redirect()->route('consultations.browse', ['teacher' => $availability->teacher_id])->with('status', 'Consultation request added to the teacher’s queue.');
    }

    public function requestTime(Request $request, User $teacher): RedirectResponse
    {
        $this->requireRole('student');
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'topic' => ['required', 'string', 'max:500'],
        ]);
        $start = Carbon::parse($data['date'].' '.$data['starts_at']);
        $end = Carbon::parse($data['date'].' '.$data['ends_at']);
        DB::transaction(function () use ($request, $teacher, $data, $start, $end) {
            $teacher = User::whereKey($teacher->id)->lockForUpdate()->firstOrFail();
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($teacher->isTeacher() && $teacher->teacher?->consultation_approved, 403, 'This teacher is not approved for consultations.');
            if (! $start->isFuture()) throw ValidationException::withMessages(['starts_at' => 'Choose a consultation time in the future.']);
            $maximum = $teacher->teacher->max_consultation_minutes;
            if ($start->diffInMinutes($end) > $maximum) throw ValidationException::withMessages(['ends_at' => "This teacher allows a maximum of {$maximum} minutes per consultation."]);
            if (ConsultationAppointment::where('teacher_id', $teacher->id)->reservesTeacherTime()->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'This teacher already has a reserved consultation at this time.']);
            }
            if (ConsultationAppointment::where('student_id', $request->user()->id)->whereNotIn('status', ['cancelled', 'rejected'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'You already have a consultation or request at this time.']);
            }
            ConsultationAppointment::create([
                'teacher_id' => $teacher->id, 'student_id' => $request->user()->id,
                'starts_at' => $start, 'ends_at' => $end, 'topic' => $data['topic'],
                'status' => 'pending', 'is_proposed' => true,
            ]);
        });
        return redirect()->route('consultations.dashboard')->with('status', 'Proposed time sent. The teacher must accept it before the requested start time.');
    }

    public function teacher(): View
    {
        $this->requireRole('teacher');
        $user = request()->user();
        $profile = $user->teacher()->firstOrCreate();
        $calendarInput = request()->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'day' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $month = ! empty($calendarInput['month'])
            ? Carbon::createFromFormat('!Y-m', $calendarInput['month'])->startOfMonth()
            : (! empty($calendarInput['day']) ? Carbon::parse($calendarInput['day'])->startOfMonth() : now()->startOfMonth());
        $selectedDay = ! empty($calendarInput['day'])
            ? Carbon::parse($calendarInput['day'])
            : ($month->isSameMonth(today()) ? today() : $month->copy());
        if (! $selectedDay->isSameMonth($month)) {
            throw ValidationException::withMessages(['day' => 'Select a date in the displayed month.']);
        }
        $calendarAvailability = ConsultationAvailability::where('teacher_id', $user->id)->whereNull('cancelled_at')
            ->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->orderBy('date')->orderBy('starts_at')->get()->groupBy(fn ($period) => $period->date->toDateString());
        $calendarAppointments = ConsultationAppointment::where('teacher_id', $user->id)->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereBetween('starts_at', [$month, $month->copy()->endOfMonth()])->with(['student', 'availability'])
            ->orderBy('starts_at')->get()->groupBy(fn ($booking) => $booking->starts_at->toDateString());

        return view('teacher.calendar', compact('profile', 'month', 'selectedDay', 'calendarAvailability', 'calendarAppointments'));
    }

    public function teacherRequests(Request $request): View
    {
        $this->requireRole('teacher');
        $profile = $request->user()->teacher()->firstOrCreate();
        $filters = $request->validate(['status' => ['nullable', 'in:pending,confirmed,completed,cancelled,rejected']]);
        $status = $filters['status'] ?? null;
        $query = ConsultationAppointment::where('teacher_id', $request->user()->id);
        $statusCounts = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $appointments = $query->when($status, fn ($query) => $query->where('status', $status))
            ->with(['student', 'availability'])->orderBy('starts_at')->orderBy('id')->paginate(12)->withQueryString();

        return view('teacher.requests', compact('profile', 'appointments', 'status', 'statusCounts'));
    }

    public function teacherAvailability(Request $request): View
    {
        $this->requireRole('teacher');
        $profile = $request->user()->teacher()->firstOrCreate();
        $availability = ConsultationAvailability::where('teacher_id', $request->user()->id)->whereNull('cancelled_at')
            ->whereDate('date', '>=', today())->orderBy('date')->orderBy('starts_at')->orderBy('id')
            ->paginate(10)->withQueryString();

        return view('teacher.availability', compact('profile', 'availability'));
    }

    public function teacherPreferences(Request $request): View
    {
        $this->requireRole('teacher');

        return view('teacher.settings', ['profile' => $request->user()->teacher()->firstOrCreate()]);
    }

    public function availability(Request $request): RedirectResponse
    {
        $this->requireRole('teacher');
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);
        DB::transaction(function () use ($request, $data) {
            $teacher = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($teacher->teacher?->consultation_approved, 403, 'An administrator must approve your consultation permission first.');
            // Retain the legacy column for historical data, not fixed slots.
            $data['slot_minutes'] = $teacher->teacher->max_consultation_minutes;
            $start = Carbon::parse($data['date'].' '.$data['starts_at']);
            $end = Carbon::parse($data['date'].' '.$data['ends_at']);
            if (! $start->isFuture()) throw ValidationException::withMessages(['starts_at' => 'Availability must start in the future.']);
            $overlap = ConsultationAvailability::query()->where('teacher_id', $teacher->id)->whereNull('cancelled_at')->whereDate('date', $data['date'])->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at'])->exists();
            if ($overlap) throw ValidationException::withMessages(['date' => 'This time overlaps another availability window you published.']);
            ConsultationAvailability::create(['teacher_id' => $teacher->id] + $data);
        });
        return redirect()->route('consultations.teacher.availability')->with('status', 'Consultation availability published.');
    }

    public function updateAppointment(Request $request, ConsultationAppointment $appointment): RedirectResponse
    {
        $this->requireRole('teacher');
        abort_unless($appointment->teacher_id === $request->user()->id, 404);
        $data = $request->validate(['status' => ['required', 'in:confirmed,rejected,completed,cancelled']]);
        DB::transaction(function () use ($request, $appointment, $data) {
            $teacher = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            User::whereKey($appointment->student_id)->lockForUpdate()->firstOrFail();
            $period = $appointment->availability_id ? ConsultationAvailability::whereKey($appointment->availability_id)->lockForUpdate()->firstOrFail() : null;
            $booking = ConsultationAppointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $allowed = match ($booking->status) {
                'pending' => ['confirmed', 'rejected', 'cancelled'],
                'confirmed' => ['completed', 'cancelled'],
                default => [],
            };
            if (! in_array($data['status'], $allowed, true) || $period?->cancelled_at) {
                throw ValidationException::withMessages(['status' => 'This booking can no longer be changed to that status.']);
            }
            if ($data['status'] === 'confirmed') {
                abort_unless($teacher->teacher?->consultation_approved, 403, 'Your consultation permission is not approved.');
                if (! $booking->starts_at->isFuture()) throw ValidationException::withMessages(['status' => 'A past booking cannot be confirmed.']);
                $teacherConflict = ConsultationAppointment::where('teacher_id', $teacher->id)->where('id', '!=', $booking->id)->reservesTeacherTime()
                    ->where('starts_at', '<', $booking->ends_at)->where('ends_at', '>', $booking->starts_at)->exists();
                $studentConflict = ConsultationAppointment::where('student_id', $booking->student_id)->where('id', '!=', $booking->id)->whereNotIn('status', ['cancelled', 'rejected'])
                    ->where('starts_at', '<', $booking->ends_at)->where('ends_at', '>', $booking->starts_at)->exists();
                if ($teacherConflict || $studentConflict) throw ValidationException::withMessages(['status' => 'This time now overlaps another consultation.']);
            }
            $booking->update($data);
            if ($booking->status === 'confirmed') ProposedConsultationRequests::rejectOverlapping($booking);
        });
        return redirect()->route('consultations.teacher.requests')->with('status', 'Consultation status updated.');
    }

    public function teacherSettings(Request $request): RedirectResponse
    {
        $this->requireRole('teacher');
        $data = $request->validate([
            'max_consultation_minutes' => ['required', 'integer', 'in:30,60'],
            'auto_approve_bookings' => ['required', 'boolean'],
            'show_student_names' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($request, $data) {
            // Serialize limit changes with booking validation for this teacher.
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $request->user()->teacher()->updateOrCreate([], $data);
        });

        return redirect()->route('consultations.teacher.preferences')->with('status', 'Consultation preferences saved. The time limit applies to new bookings; existing bookings are unchanged.');
    }

    public function cancelAvailability(Request $request, ConsultationAvailability $availability): RedirectResponse
    {
        $this->requireRole('teacher');
        abort_unless($availability->teacher_id === $request->user()->id, 404);
        DB::transaction(function () use ($request, $availability) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $period = ConsultationAvailability::whereKey($availability->id)->lockForUpdate()->firstOrFail();
            if ($period->cancelled_at) return;
            $period->update(['cancelled_at' => now()]);
            $period->appointments()->whereIn('status', ['pending', 'confirmed'])->update(['status' => 'rejected']);
        });

        return redirect()->route('consultations.teacher.availability')->with('status', 'Availability cancelled. All pending and confirmed bookings in this window were automatically rejected.');
    }

    public function cancelBooking(Request $request, ConsultationAppointment $appointment): RedirectResponse
    {
        $this->requireRole('student');
        abort_unless($appointment->student_id === $request->user()->id, 404);
        DB::transaction(function () use ($appointment) {
            User::whereKey($appointment->teacher_id)->lockForUpdate()->firstOrFail();
            if ($appointment->availability_id) ConsultationAvailability::whereKey($appointment->availability_id)->lockForUpdate()->firstOrFail();
            $booking = ConsultationAppointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            if (! in_array($booking->status, ['pending', 'confirmed'], true) || ! $booking->starts_at->isFuture()) {
                throw ValidationException::withMessages(['booking' => 'Only upcoming pending or confirmed bookings can be cancelled.']);
            }
            $booking->update(['status' => 'cancelled']);
        });

        return redirect()->route('consultations.dashboard')->with('status', 'Booking cancelled. You can book another available time from the teacher calendar.');
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('portal.login');
    }

    private function requireRole(string $role): void
    {
        abort_unless(request()->user() && ($role === 'teacher' ? request()->user()->isTeacher() : request()->user()->isStudent()), 403);
    }
}
