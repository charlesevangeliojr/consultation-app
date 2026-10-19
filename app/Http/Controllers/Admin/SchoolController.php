<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function index(): View
    {
        return view('admin.school', [
            'gradeLevels' => GradeLevel::whereIn('name', GradeLevel::STANDARD_NAMES)->with('sections')->orderByRaw('LENGTH(name)')->orderBy('name')->get(),
        ]);
    }

    public function registrationGrades(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'grades' => ['sometimes', 'array'],
            'grades.*' => ['required', 'integer', 'distinct', Rule::exists('grade_levels', 'id')->whereIn('name', GradeLevel::STANDARD_NAMES)],
        ]);
        DB::transaction(function () use ($data) {
            // Lock the full set so simultaneous edits cannot partially interleave.
            $grades = GradeLevel::whereIn('name', GradeLevel::STANDARD_NAMES)->lockForUpdate()->get();
            $enabled = collect($data['grades'] ?? [])->map(fn ($id) => (int) $id);
            foreach ($grades as $grade) $grade->update(['registration_enabled' => $enabled->contains($grade->id)]);
        });
        return back()->with('status', 'Registration grades updated. Only checked grades appear during student registration.');
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $model = $this->catalogModel($type);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique((new $model)->getTable())->when($type === 'sections', fn ($rule) => $rule->where('grade_level_id', $request->input('grade_level_id')))],
            'grade_level_id' => [$type === 'sections' ? 'required' : 'exclude', 'integer', 'exists:grade_levels,id'],
        ]);
        $model::create($data);

        return back()->with('status', 'Section added. It appears in registration when its grade is enabled.');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->catalogModel($type);
        $item = $model::findOrFail($id);
        $unique = Rule::unique($item->getTable())->ignore($id);
        if ($type === 'sections') $unique->where('grade_level_id', $item->grade_level_id);
        $item->update($request->validate(['name' => ['required', 'string', 'max:100', $unique]]));

        return back()->with('status', 'School option renamed.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $model = $this->catalogModel($type);
        $item = $model::findOrFail($id);
        $used = match ($type) {
            'sections' => \App\Models\Student::where('section_id', $id)->exists(),
        };
        if ($used) throw ValidationException::withMessages(['catalog' => 'This option is in use. Reassign its students/teachers or sections before deleting it.']);
        try {
            $item->delete();
        } catch (\Illuminate\Database\QueryException $exception) {
            // Foreign keys also protect against a registration racing this request.
            if (! in_array((string) $exception->getCode(), ['23000', '23503'], true)) throw $exception;
            throw ValidationException::withMessages(['catalog' => 'This option is in use and cannot be deleted.']);
        }

        return back()->with('status', 'Unused school option deleted.');
    }

    public function updateProfile(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManage($user), 403);
        abort_unless($user->isStudent() || $user->isTeacher(), 404);
        if ($user->isStudent()) {
            $data = $request->validate([
                'contact_number' => ['nullable', 'regex:/^\+639[0-9]{9}$/'],
                'birthday' => ['nullable', 'date', 'before:today'],
                'gender' => ['nullable', Rule::in(['Male', 'Female'])],
                'address' => ['nullable', 'string', 'max:2000'],
                'status' => ['sometimes', Rule::in(['active', 'inactive'])],
                'lrn' => ['nullable', 'digits:12', Rule::unique('students')->ignore($user->student?->id)],
                'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
                'section_id' => ['required', 'integer', Rule::exists('sections', 'id')->where('grade_level_id', $request->input('grade_level_id'))],
            ]);
            DB::transaction(function () use ($user, $data) {
                if (isset($data['status'])) {
                    \App\Support\AccountActivity::setStudentStatus($user, $data['status']);
                }
                $user->student()->updateOrCreate([], collect($data)->except('status')->all());
            });
        } else {
            $data = $request->validate([
                'consultation_approved' => ['required', 'boolean'],
            ]);
            DB::transaction(function () use ($user, $data) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $user->teacher()->updateOrCreate([], ['consultation_approved' => $data['consultation_approved']]);
            });
        }

        return back()->with('status', $user->isTeacher()
            ? ($data['consultation_approved'] ? 'Teacher scheduling approved. They can publish availability after completing their profile.' : 'Teacher scheduling revoked. New schedules, booking requests, and confirmations are blocked.')
            : 'School profile updated.');
    }

    public function monitoring(Request $request): View
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m'], 'day' => ['nullable', 'date_format:Y-m-d']]);
        $month = !empty($data['month']) ? Carbon::createFromFormat('!Y-m', $data['month'])
            : (!empty($data['day']) ? Carbon::parse($data['day'])->startOfMonth() : now()->startOfMonth());
        $selectedDay = !empty($data['day']) ? Carbon::parse($data['day']) : ($month->isSameMonth(today()) ? today() : $month->copy());
        if (!$selectedDay->isSameMonth($month)) throw ValidationException::withMessages(['day' => 'Select a date in the displayed month.']);
        $monthEnd = $month->copy()->addMonth();
        $bookingDates = ConsultationAppointment::where('starts_at', '>=', $month)->where('starts_at', '<', $monthEnd)
            ->selectRaw('DATE(starts_at) as booking_date, status, COUNT(*) as total')->groupByRaw('DATE(starts_at), status')
            ->get()->groupBy('booking_date');
        $availabilityDates = ConsultationAvailability::whereNull('cancelled_at')->where('date', '>=', $month->toDateString())->where('date', '<', $monthEnd->toDateString())
            ->selectRaw('date, COUNT(*) as total')->groupBy('date')->pluck('total', 'date');
        return view('admin.consultations', [
            'month' => $month, 'selectedDay' => $selectedDay, 'bookingDates' => $bookingDates, 'availabilityDates' => $availabilityDates,
            'appointments' => ConsultationAppointment::with(['teacher', 'student', 'availability'])->where('starts_at', '>=', $selectedDay)->where('starts_at', '<', $selectedDay->copy()->addDay())->orderBy('starts_at')->orderBy('id')->paginate(25),
            'availability' => ConsultationAvailability::with('teacher')->whereDate('date', $selectedDay)->withCount(['appointments as queued_count' => fn ($query) => $query->whereIn('status', ['pending', 'confirmed'])])->orderBy('starts_at')->paginate(15, ['*'], 'availability_page'),
            'teachers' => User::with(['teacher', 'role'])->whereHas('role', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%teacher%'])->orWhereRaw('LOWER(name) LIKE ?', ['%faculty%']))->orderBy('name')->get(),
        ]);
    }

    private function catalogModel(string $type): string
    {
        return match ($type) {
            'sections' => Section::class,
            default => abort(404),
        };
    }
}
