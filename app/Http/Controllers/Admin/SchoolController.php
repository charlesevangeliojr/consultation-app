<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationAppointment;
use App\Models\ConsultationAvailability;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function index(): View
    {
        return view('admin.school', [
            'gradeLevels' => GradeLevel::with('sections')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $model = $this->catalogModel($type);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique((new $model)->getTable())->when($type === 'sections', fn ($rule) => $rule->where('grade_level_id', $request->input('grade_level_id')))],
            'grade_level_id' => [$type === 'sections' ? 'required' : 'exclude', 'integer', 'exists:grade_levels,id'],
        ]);
        $model::create($data);

        return back()->with('status', 'School list updated. The new option is available during registration.');
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
            'grade-levels' => $item->sections()->exists() || \App\Models\Student::where('grade_level_id', $id)->exists(),
            'sections' => \App\Models\Student::where('section_id', $id)->exists(),
            'subjects' => $item->teachers()->exists(),
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
                'status' => ['required', Rule::in(['active', 'inactive'])],
                'lrn' => ['nullable', 'digits:12', Rule::unique('students')->ignore($user->student?->id)],
                'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
                'section_id' => ['required', 'integer', Rule::exists('sections', 'id')->where('grade_level_id', $request->input('grade_level_id'))],
            ]);
            $user->student()->updateOrCreate([], $data);
        } else {
            $data = $request->validate([
                'consultation_approved' => ['required', 'boolean'],
                'subject_ids' => ['required', 'array', 'min:1'],
                'subject_ids.*' => ['integer', 'distinct', 'exists:subjects,id'],
            ]);
            DB::transaction(function () use ($user, $data) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $teacher = $user->teacher()->updateOrCreate([], ['consultation_approved' => $data['consultation_approved']]);
                $teacher->subjects()->sync($data['subject_ids']);
            });
        }

        return back()->with('status', 'School profile updated.');
    }

    public function monitoring(): View
    {
        return view('admin.consultations', [
            'appointments' => ConsultationAppointment::with(['teacher', 'student', 'availability'])->orderByDesc('starts_at')->paginate(25),
            'availability' => ConsultationAvailability::with('teacher')->withCount(['appointments as queued_count' => fn ($query) => $query->whereIn('status', ['pending', 'confirmed'])])->orderByDesc('date')->orderBy('starts_at')->paginate(15, ['*'], 'availability_page'),
            'teachers' => User::with(['teacher.subjects', 'role'])->whereHas('role', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%teacher%'])->orWhereRaw('LOWER(name) LIKE ?', ['%faculty%']))->orderBy('name')->get(),
        ]);
    }

    private function catalogModel(string $type): string
    {
        return match ($type) {
            'grade-levels' => GradeLevel::class,
            'sections' => Section::class,
            'subjects' => Subject::class,
            default => abort(404),
        };
    }
}
