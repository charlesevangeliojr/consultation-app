<?php

namespace App\Observers;

use App\Models\Teacher;
use App\Models\User;
use App\Support\BrowserPush;

class TeacherPushObserver
{
    public function created(Teacher $teacher): void
    {
        if ($teacher->consultation_approved) return;
        $account = $teacher->user;
        // Inbox alerts are for all authorized admins, not just push subscribers.
        User::with('role')->get()->each(function ($admin) use ($account) {
            if ($admin->canAccess('accounts', 'view') && $admin->canManage($account, 'edit')) {
                BrowserPush::notify($admin->id, 'A teacher account needs scheduling approval. Open Accounts to review it.', '/admin/accounts');
            }
        });
    }

    public function updated(Teacher $teacher): void
    {
        if (! $teacher->wasChanged('consultation_approved')) {
            return;
        }

        if ($teacher->consultation_approved) {
            if ($teacher->hasCompleteProfile()) {
                BrowserPush::notify(
                    $teacher->user_id,
                    'Your scheduling permission was approved. You can now publish availability.',
                    '/consultations/teacher/availability',
                    'Scheduling permission update'
                );
            } else {
                BrowserPush::notify(
                    $teacher->user_id,
                    'Your scheduling permission was approved. Complete your profile to publish availability.',
                    '/consultations/teacher/profile',
                    'Scheduling permission update'
                );
            }

            return;
        }

        BrowserPush::notify(
            $teacher->user_id,
            'Your scheduling permission was revoked. Contact an administrator if you need access restored.',
            '',
            'Scheduling permission update'
        );
    }
}
