<?php

namespace App\Console\Commands;

use App\Models\ConsultationAppointment;
use App\Support\BrowserPush;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendConsultationReminders extends Command
{
    protected $signature = 'consultations:send-reminders';
    protected $description = 'Send due reminders for upcoming confirmed student consultations';

    public function handle(): int
    {
        if (! Schema::hasTable('student_reminder_settings') || ! Schema::hasTable('consultation_reminders') || ! Schema::hasTable('account_notifications')) {
            $this->error('Reminder database setup is missing. Apply the latest migrations.');
            return self::FAILURE;
        }

        $now = now();
        $count = 0;
        ConsultationAppointment::query()->where('status', 'confirmed')
            ->where('starts_at', '>', $now)->where('starts_at', '<=', $now->copy()->addDay())
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('consultation_reminders')
                ->whereColumn('consultation_reminders.appointment_id', 'consultation_appointments.id'))
            ->orderBy('id')->chunkById(100, function ($appointments) use (&$count) {
                foreach ($appointments as $appointment) {
                    $sent = DB::transaction(function () use ($appointment) {
                        $booking = ConsultationAppointment::whereKey($appointment->id)->lockForUpdate()->first();
                        if (! $booking || $booking->status !== 'confirmed' || $booking->starts_at->lte(now())) return false;
                        $minutes = DB::table('student_reminder_settings')->where('user_id', $booking->student_id)->value('minutes_before');
                        if ($minutes === null || $booking->starts_at->gt(now()->addMinutes((int) $minutes))) return false;
                        if (DB::table('consultation_reminders')->where('appointment_id', $booking->id)->exists()) return false;

                        DB::table('consultation_reminders')->insert(['appointment_id' => $booking->id, 'sent_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                        BrowserPush::notify($booking->student_id, 'You have an upcoming consultation. Open your account for details.', '/consultations/student/consultations');
                        return true;
                    });
                    if ($sent) $count++;
                }
            });
        $this->info("Sent {$count} consultation reminders.");
        return self::SUCCESS;
    }
}
