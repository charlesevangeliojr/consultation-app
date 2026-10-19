<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\GradeLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('consultations.home');
        }

        $role = $request->query('role');
        if (! in_array($role, ['student', 'teacher'], true)) {
            return view('auth.register-choice');
        }

        return view('auth.register', [
            'selectedRole' => $role,
            'gradeLevels' => GradeLevel::forRegistration()->with('sections')->orderByRaw('LENGTH(name)')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'email' => ['exclude_unless:role,teacher', 'required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['student', 'teacher'])],
            'password' => ['required', 'confirmed', 'string', 'min:8'],
            'contact_number' => ['required_if:role,student', 'regex:/^9[0-9]{9}$/'],
            'birthday' => ['required_if:role,student', 'date', 'before:today'],
            'gender' => ['required_if:role,student', Rule::in(['Male', 'Female'])],
            'house_number' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:150'],
            'barangay' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:150'],
            'lrn' => ['required_if:role,student', 'digits:12', 'unique:students,lrn'],
            'grade_level_id' => ['exclude_unless:role,student', 'required', 'integer', Rule::exists('grade_levels', 'id')->where('registration_enabled', true)->whereIn('name', GradeLevel::STANDARD_NAMES)],
            'section_id' => ['exclude_unless:role,student', 'required', 'integer', Rule::exists('sections', 'id')->where('grade_level_id', $request->input('grade_level_id'))],
        ]);

        $user = DB::transaction(function () use ($validated) {
            if ($validated['role'] === 'student' && !GradeLevel::forRegistration()->whereKey($validated['grade_level_id'])->lockForUpdate()->first(['id'])) {
                throw ValidationException::withMessages(['grade_level_id' => 'This grade is no longer available for registration. Please choose an available grade.']);
            }
            $roleName = $validated['role'] === 'teacher' ? 'Teacher' : 'Student';
            $role = Role::query()->firstOrCreate(['name' => $roleName], ['is_super_admin' => false]);
            $name = collect([
                $validated['first_name'],
                $validated['middle_name'] ?? null,
                $validated['last_name'],
                $validated['suffix'] ?? null,
            ])->filter()->implode(' ');

            $user = User::create([
                'name' => $name,
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id,
            ]);

            if ($validated['role'] === 'teacher') {
                Teacher::create(['user_id' => $user->id]);
            } else {
                $address = collect([
                    $validated['house_number'] ?? null,
                    $validated['region'] ?? null,
                    $validated['barangay'] ?? null,
                    $validated['city'] ?? null,
                    $validated['province'] ?? null,
                ])->filter()->implode(', ');
                Student::create([
                    'user_id' => $user->id,
                    'contact_number' => isset($validated['contact_number']) ? '+63'.$validated['contact_number'] : null,
                    'birthday' => $validated['birthday'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'address' => $address ?: null,
                    'house_number' => $validated['house_number'] ?? null,
                    'region' => $validated['region'] ?? null,
                    'province' => $validated['province'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'barangay' => $validated['barangay'] ?? null,
                    'lrn' => $validated['lrn'] ?? null,
                    'grade_level_id' => $validated['grade_level_id'],
                    'section_id' => $validated['section_id'],
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('consultations.home')->with('status', 'Your account has been created. Welcome!');
    }
}
