<?php

namespace App\Observers;

use App\Models\ConsultationAppointment;
use App\Support\BrowserPush;

class ConsultationPushObserver
{
    public function created(ConsultationAppointment $booking): void
    {
        if ($booking->status === 'pending') {
            BrowserPush::notify($booking->teacher_id, 'A new consultation request is waiting for your review.', '/consultations/teacher/requests');
        } elseif ($booking->status === 'confirmed') {
            BrowserPush::notify($booking->teacher_id, 'A consultation has been booked. Open your account to review it.', '/consultations/teacher/requests');
            BrowserPush::notify($booking->student_id, 'Your consultation is confirmed. Open your account for details.', '/consultations/student/consultations');
        }
    }

    public function updated(ConsultationAppointment $booking): void
    {
        if (! $booking->wasChanged('status')) return;
        $message = match ($booking->status) {
            'confirmed' => 'Your consultation is confirmed. Open your account for details.',
            'rejected' => 'A consultation request was rejected. Open your account to review the update.',
            'cancelled' => 'A consultation was cancelled. Open your account to review the update.',
            'completed' => 'A consultation was marked completed. Open your account for details.',
            default => null,
        };
        if ($message) BrowserPush::notify($booking->student_id, $message, '/consultations/student/consultations');
        if ($booking->status === 'cancelled' && auth()->id() !== $booking->teacher_id) {
            BrowserPush::notify($booking->teacher_id, 'A consultation was cancelled. Open your requests to review it.', '/consultations/teacher/requests');
        }
    }
}
