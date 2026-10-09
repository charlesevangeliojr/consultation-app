<?php

namespace App\Http\Controllers;

use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\User;
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
        if ($user->isStudent()) return redirect()->route('consultations.browse');
        return redirect()->route('admin.dashboard');
    }

    public function browse(): View
    {
        $this->requireRole('student');
        $teachers = User::query()->with('role')->whereHas('role', function ($query) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%teacher%'])->orWhereRaw('LOWER(name) LIKE ?', ['%faculty%']);
        })->orderBy('name')->get();
        $selectedTeacher = request()->integer('teacher') ? User::find(request()->integer('teacher')) : $teachers->first();
        abort_if($selectedTeacher && ! $selectedTeacher->isTeacher(), 404);
        $availability = $selectedTeacher ? ConsultationAvailability::query()->where('teacher_id', $selectedTeacher->id)->whereDate('date', '>=', today())->with('appointments')->orderBy('date')->orderBy('starts_at')->get() : collect();
        $bookedSlots = $availability->flatMap(function ($period) {
            return $period->appointments->whereNotIn('status', ['cancelled', 'rejected'])->map(fn ($appointment) => $appointment->starts_at->format('Y-m-d H:i'));
        })->all();

        return view('consultations.browse', compact('teachers', 'selectedTeacher', 'availability', 'bookedSlots'));
    }

    public function book(Request $request, ConsultationAvailability $availability): RedirectResponse
    {
        $this->requireRole('student');
        $data = $request->validate(['starts_at' => ['required', 'date'], 'topic' => ['nullable', 'string', 'max:500']]);
        $start = Carbon::parse($data['starts_at']);
        $end = $start->copy()->addMinutes($availability->slot_minutes);

        DB::transaction(function () use ($request, $availability, $start, $end, $data) {
            $period = ConsultationAvailability::query()->whereKey($availability->id)->lockForUpdate()->firstOrFail();
            abort_unless($period->date->isFuture() || $period->date->isToday(), 422, 'This consultation period has passed.');
            $periodStart = Carbon::parse($period->date->toDateString().' '.$period->starts_at);
            $periodEnd = Carbon::parse($period->date->toDateString().' '.$period->ends_at);
            if ($start->lt($periodStart) || $end->gt($periodEnd) || $periodStart->diffInMinutes($start) % $period->slot_minutes !== 0) {
                throw ValidationException::withMessages(['starts_at' => 'Choose a valid time within the teacher’s published availability.']);
            }
            $conflict = ConsultationAppointment::query()->where('teacher_id', $period->teacher_id)->whereNotIn('status', ['cancelled', 'rejected'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->lockForUpdate()->exists();
            if ($conflict) throw ValidationException::withMessages(['starts_at' => 'That time was just booked. Please select another available time.']);

            ConsultationAppointment::create([
                'availability_id' => $period->id,
                'teacher_id' => $period->teacher_id,
                'student_id' => $request->user()->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'topic' => $data['topic'] ?? null,
                'status' => 'pending',
            ]);
        });

        return redirect()->route('consultations.browse', ['teacher' => $availability->teacher_id])->with('status', 'Consultation request added to the teacher’s queue.');
    }

    public function teacher(): View
    {
        $this->requireRole('teacher');
        $user = request()->user();
        $availability = ConsultationAvailability::query()->where('teacher_id', $user->id)->whereDate('date', '>=', today())->with('appointments')->orderBy('date')->orderBy('starts_at')->get();
        $appointments = ConsultationAppointment::query()->where('teacher_id', $user->id)->with(['student', 'availability'])->whereNotIn('status', ['cancelled', 'rejected'])->orderBy('starts_at')->get();
        return view('consultations.teacher', compact('availability', 'appointments'));
    }

    public function availability(Request $request): RedirectResponse
    {
        $this->requireRole('teacher');
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'slot_minutes' => ['required', 'integer', 'in:15,20,30,45,60'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);
        $start = Carbon::parse($data['date'].' '.$data['starts_at']);
        $end = Carbon::parse($data['date'].' '.$data['ends_at']);
        if ($start->diffInMinutes($end) < $data['slot_minutes'] || $start->diffInMinutes($end) % $data['slot_minutes'] !== 0) {
            throw ValidationException::withMessages(['ends_at' => 'The consultation window must fit complete appointment slots.']);
        }
        $overlap = ConsultationAvailability::query()->where('teacher_id', $request->user()->id)->whereDate('date', $data['date'])->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at'])->exists();
        if ($overlap) throw ValidationException::withMessages(['date' => 'This time overlaps another availability window you published.']);
        ConsultationAvailability::create(['teacher_id' => $request->user()->id] + $data);
        return redirect()->route('consultations.teacher')->with('status', 'Consultation availability published.');
    }

    public function updateAppointment(Request $request, ConsultationAppointment $appointment): RedirectResponse
    {
        $this->requireRole('teacher');
        abort_unless($appointment->teacher_id === $request->user()->id, 404);
        $data = $request->validate(['status' => ['required', 'in:confirmed,rejected,completed,cancelled']]);
        $appointment->update($data);
        return redirect()->route('consultations.teacher')->with('status', 'Consultation status updated.');
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
