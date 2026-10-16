<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Support\TeacherPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class TeacherProfileController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()->isTeacher(), 403);

        $teacher = $request->user()->teacher()->firstOrCreate();

        return view('teacher.profile', [
            'user' => $request->user(),
            'teacher' => $teacher,
            'missingFields' => $teacher->missingProfileFields(),
            'phoneCountries' => config('phone_countries'),
            'phone' => TeacherPhone::split($teacher->contact_number, $teacher->phone_country),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isTeacher(), 403);

        $teacher = $user->teacher()->firstOrCreate();
        $rules = Teacher::profileDetailRules();
        unset($rules['contact_number']);
        $countries = config('phone_countries');
        $countryKey = $request->input('phone_country');
        $country = is_string($countryKey) ? ($countries[$countryKey] ?? null) : null;
        $rules['phone_number'] = ['required', 'string', 'max:15', 'regex:/^'.($country['pattern'] ?? '[0-9]{7,14}').'$/'];
        $rules['email'][] = Rule::unique('users', 'email')->ignore($user->id);
        $rules['profile_photo'] = [$teacher->profile_photo_path ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
        $validated = $request->validate($rules, [
            'phone_number.regex' => 'Enter a valid national number for the selected country. Do not include the country code or a leading trunk zero.',
            'profile_photo.required' => 'Add a profile picture to complete your teacher profile.',
        ], ['gender' => 'sex', 'consultation_offerings' => 'what you offer']);
        $validated['contact_number'] = '+'.$country['code'].$validated['phone_number'];
        $validated['address'] = collect(['house_number', 'barangay', 'city', 'province', 'region', 'postal_code'])
            ->map(fn ($field) => $validated[$field] ?? null)->filter()->implode(', ');

        $photoPath = $request->hasFile('profile_photo')
            ? $request->file('profile_photo')->store('teacher-profiles', 'public') : null;
        $oldPhotoPath = null;
        try {
            DB::transaction(function () use ($user, $validated, $photoPath, &$oldPhotoPath) {
                $teacher = $user->teacher()->firstOrCreate();
                $oldPhotoPath = $teacher->profile_photo_path;
                $user->name = $validated['name'];
                if ($user->email !== $validated['email']) {
                    $user->email_verified_at = null;
                }
                $user->email = $validated['email'];
                $user->save();
                $details = collect($validated)->except(['name', 'email', 'profile_photo', 'phone_number'])->all();
                if ($photoPath) {
                    $details['profile_photo_path'] = $photoPath;
                }
                $teacher->update($details);
            });
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            throw $exception;
        }
        if ($photoPath && $oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return redirect()->route('consultations.teacher-profile.edit')->with('status', 'Teacher profile updated successfully.');
    }
}
