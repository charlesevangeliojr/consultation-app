<?php

namespace App\Http\Controllers;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\User;
use App\Models\SiteSetting;
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
        $bookedSlots = $availability->flatMap(function ($period) {
            return $period->appointments->whereNotIn('status', ['cancelled', 'rejected'])->map(fn ($appointment) => $appointment->starts_at->format('Y-m-d H:i'));
        })->all();

        $showStudentNames = $selectedTeacher?->teacher?->queueNamesVisible(SiteSetting::current()) ?? false;
        $monthInput = request()->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = isset($monthInput['month']) ? Carbon::createFromFormat('!Y-m', $monthInput['month'])->startOfMonth() : now()->startOfMonth();

        return view('student.browse', compact('teachers', 'selectedTeacher', 'availability', 'bookedSlots', 'showStudentNames', 'month'));
    }

    public function book(Request $request, ConsultationAvailability $availability): RedirectResponse
    {
        $this->requireRole('student');
        $data = $request->validate(['starts_at' => ['required', 'date', 'after:now'], 'topic' => ['required', 'string', 'max:500']]);
        $start = Carbon::parse($data['starts_at']);

        DB::transaction(function () use ($request, $availability, $start, $data) {
            $teacher = User::whereKey($availability->teacher_id)->lockForUpdate()->firstOrFail();
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($teacher->isTeacher() && $teacher->teacher?->consultation_approved, 403, 'This teacher is not approved for consultations.');
            $period = ConsultationAvailability::query()->whereKey($availability->id)->lockForUpdate()->firstOrFail();
            abort_if($period->cancelled_at, 422, 'This availability has been cancelled.');
            $end = $start->copy()->addMinutes($period->slot_minutes);
            abort_unless($period->date->isFuture() || $period->date->isToday(), 422, 'This consultation period has passed.');
            $periodStart = Carbon::parse($period->date->toDateString().' '.$period->starts_at);
            $periodEnd = Carbon::parse($period->date->toDateString().' '.$period->ends_at);
            if ($start->lt($periodStart) || $end->gt($periodEnd) || $periodStart->diffInMinutes($start) % $period->slot_minutes !== 0) {
                throw ValidationException::withMessages(['starts_at' => 'Choose a valid time within the teacher’s published availability.']);
            }
            $conflict = ConsultationAppointment::query()->where('teacher_id', $period->teacher_id)->whereNotIn('status', ['cancelled', 'rejected'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->lockForUpdate()->exists();
            if ($conflict) throw ValidationException::withMessages(['starts_at' => 'That time was just booked. Please select another available time.']);
            $studentConflict = ConsultationAppointment::where('student_id', $request->user()->id)->whereNotIn('status', ['cancelled', 'rejected'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
            if ($studentConflict) throw ValidationException::withMessages(['starts_at' => 'You already have a consultation at this time.']);

            ConsultationAppointment::create([
                'availability_id' => $period->id,
                'teacher_id' => $period->teacher_id,
                'student_id' => $request->user()->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'topic' => $data['topic'] ?? null,
                'status' => $teacher->teacher->auto_approve_bookings ? 'confirmed' : 'pending',
            ]);
        });

        return redirect()->route('consultations.browse', ['teacher' => $availability->teacher_id])->with('status', 'Consultation request added to the teacher’s queue.');
    }

    public function teacher(): View
    {
        $this->requireRole('teacher');
        $user = request()->user();
        $profile = $user->teacher()->firstOrCreate();
        $availability = ConsultationAvailability::query()->where('teacher_id', $user->id)->whereNull('cancelled_at')->whereDate('date', '>=', today())->with('appointments')->orderBy('date')->orderBy('starts_at')->get();
        $appointments = ConsultationAppointment::query()->where('teacher_id', $user->id)->with(['student', 'availability'])->whereNotIn('status', ['cancelled', 'rejected'])->orderBy('starts_at')->get();
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

        return view('teacher.calendar', compact('availability', 'appointments', 'profile', 'month', 'selectedDay', 'calendarAvailability', 'calendarAppointments'));
    }

    public function availability(Request $request): RedirectResponse
    {
        $this->requireRole('teacher');
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'slot_minutes' => ['sometimes', 'integer', 'min:5', 'max:240'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);
        DB::transaction(function () use ($request, $data) {
            $teacher = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($teacher->teacher?->consultation_approved, 403, 'An administrator must approve your consultation permission first.');
            $data['slot_minutes'] = $data['slot_minutes'] ?? $teacher->teacher->slot_minutes;
            $start = Carbon::parse($data['date'].' '.$data['starts_at']);
            $end = Carbon::parse($data['date'].' '.$data['ends_at']);
            if (! $start->isFuture()) throw ValidationException::withMessages(['starts_at' => 'Availability must start in the future.']);
            if ($start->diffInMinutes($end) < $data['slot_minutes'] || $start->diffInMinutes($end) % $data['slot_minutes'] !== 0) {
                throw ValidationException::withMessages(['ends_at' => 'The consultation window must fit complete appointment slots.']);
            }
            $overlap = ConsultationAvailability::query()->where('teacher_id', $teacher->id)->whereNull('cancelled_at')->whereDate('date', $data['date'])->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at'])->exists();
            if ($overlap) throw ValidationException::withMessages(['date' => 'This time overlaps another availability window you published.']);
            ConsultationAvailability::create(['teacher_id' => $teacher->id] + $data);
        });
        return redirect()->route('consultations.teacher')->with('status', 'Consultation availability published.');
    }

    public function updateAppointment(Request $request, ConsultationAppointment $appointment): RedirectResponse
    {
        $this->requireRole('teacher');
        abort_unless($appointment->teacher_id === $request->user()->id, 404);
        $data = $request->validate(['status' => ['required', 'in:confirmed,rejected,completed,cancelled']]);
        DB::transaction(function () use ($request, $appointment, $data) {
            $teacher = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $period = ConsultationAvailability::whereKey($appointment->availability_id)->lockForUpdate()->firstOrFail();
            $booking = ConsultationAppointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $allowed = match ($booking->status) {
                'pending' => ['confirmed', 'rejected', 'cancelled'],
                'confirmed' => ['completed', 'cancelled'],
                default => [],
            };
            if (! in_array($data['status'], $allowed, true) || $period->cancelled_at) {
                throw ValidationException::withMessages(['status' => 'This booking can no longer be changed to that status.']);
            }
            if ($data['status'] === 'confirmed') {
                abort_unless($teacher->teacher?->consultation_approved, 403, 'Your consultation permission is not approved.');
                if (! $booking->starts_at->isFuture()) throw ValidationException::withMessages(['status' => 'A past booking cannot be confirmed.']);
            }
            $booking->update($data);
        });
        return redirect()->route('consultations.teacher')->with('status', 'Consultation status updated.');
    }

    public function teacherSettings(Request $request): RedirectResponse
    {
        $this->requireRole('teacher');
        $data = $request->validate([
            'slot_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'auto_approve_bookings' => ['required', 'boolean'],
            'show_student_names' => ['required', 'boolean'],
        ]);
        $request->user()->teacher()->updateOrCreate([], $data);

        return back()->with('status', 'Consultation preferences saved. Duration applies to new availability; existing bookings are unchanged.');
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

        return back()->with('status', 'Availability cancelled. All pending and confirmed bookings in this window were automatically rejected.');
    }

    public function cancelBooking(Request $request, ConsultationAppointment $appointment): RedirectResponse
    {
        $this->requireRole('student');
        abort_unless($appointment->student_id === $request->user()->id, 404);
        DB::transaction(function () use ($appointment) {
            User::whereKey($appointment->teacher_id)->lockForUpdate()->firstOrFail();
            ConsultationAvailability::whereKey($appointment->availability_id)->lockForUpdate()->firstOrFail();
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
