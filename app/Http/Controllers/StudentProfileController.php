<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()->isStudent(), 403);

        $student = $request->user()->student()->firstOrCreate();

        return view('student.profile', [
            'student' => $student,
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403);

        $student = $user->student()->firstOrCreate();
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'lrn' => ['nullable', 'digits:12', Rule::unique('students', 'lrn')->ignore($student->id)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user->email = $validated['email'];
        $user->save();
        $studentData = collect($validated)->except(['email', 'profile_photo'])->all();
        if ($request->hasFile('profile_photo')) {
            $oldPhotoPath = $student->profile_photo_path;
            $studentData['profile_photo_path'] = $request->file('profile_photo')->store('student-profiles', 'public');
        }
        $student->update($studentData);
        if (! empty($oldPhotoPath)) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return redirect()->route('consultations.student-profile.edit')->with('status', 'Student profile updated successfully.');
    }
}
