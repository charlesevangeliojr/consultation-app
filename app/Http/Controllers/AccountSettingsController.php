<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function student(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403);

        return view('student.settings', PushSubscriptionController::studentReminderState($user));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStudent() || $user->isTeacher(), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => $validated['password']]);

        $route = $user->isTeacher() ? 'consultations.teacher.preferences' : 'consultations.student.settings';

        return redirect()->route($route)->with('status', 'Password updated successfully.');
    }
}
