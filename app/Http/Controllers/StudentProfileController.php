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
        $addressFields = ['house_number', 'region', 'province', 'city', 'barangay'];
        if (filled($student->address) && ! collect($addressFields)->contains(fn ($field) => filled($student->{$field}))) {
            // Older registrations stored: [house number,] region, barangay, city, [province, postal code].
            $parts = array_map('trim', explode(',', $student->address));
            $regionIndex = collect($parts)->search(fn ($part) => preg_match('/\b(region|NCR|CAR|BARMM|ARMM|CALABARZON|MIMAROPA)\b/i', $part));
            if ($regionIndex !== false && $regionIndex <= 1 && count($parts) >= $regionIndex + 3) {
                $province = $parts[$regionIndex + 3] ?? null;
                $student->fill([
                    'house_number' => $regionIndex === 1 ? $parts[0] : null,
                    'region' => $parts[$regionIndex],
                    'barangay' => $parts[$regionIndex + 1],
                    'city' => $parts[$regionIndex + 2],
                    'province' => $province && ! preg_match('/^[0-9]{4}$/', $province) ? $province : null,
                ]);
            }
        }

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
        $addressFields = ['house_number', 'region', 'province', 'city', 'barangay'];
        $hasStructuredAddress = filled($student->region) || collect($addressFields)->contains(fn ($field) => $request->filled($field));
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'house_number' => ['nullable', 'string', 'max:100'],
            'region' => [$hasStructuredAddress ? 'required' : 'nullable', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:150'],
            'city' => [$hasStructuredAddress ? 'required' : 'nullable', 'string', 'max:150'],
            'barangay' => [$hasStructuredAddress ? 'required' : 'nullable', 'string', 'max:150'],
            'lrn' => ['nullable', 'digits:12', Rule::unique('students', 'lrn')->ignore($student->id)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        if ($hasStructuredAddress) {
            $validated['address'] = collect(['house_number', 'barangay', 'city', 'province', 'region'])
                ->map(fn ($field) => $validated[$field] ?? null)->filter()->implode(', ');
        }

        $user->email = $validated['email'] ?? null;
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
