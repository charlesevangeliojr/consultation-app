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
        if ($teacher->wasChanged('consultation_approved')) {
            BrowserPush::notify($teacher->user_id, $teacher->consultation_approved
                ? 'Your scheduling permission was approved. Complete your profile to publish availability.'
                : 'Your scheduling permission was revoked. Open your account for more information.', '/consultations/teacher/profile');
        }
    }
}
