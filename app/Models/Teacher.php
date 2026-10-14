<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Teacher extends Model
{
    protected $fillable = ['user_id', 'consultation_approved', 'slot_minutes', 'auto_approve_bookings', 'show_student_names'];

    protected function casts(): array
    {
        return ['consultation_approved' => 'boolean', 'slot_minutes' => 'integer', 'auto_approve_bookings' => 'boolean', 'show_student_names' => 'boolean'];
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    public function queueNamesVisible(SiteSetting $settings): bool
    {
        return match ($settings->queue_name_visibility) {
            'hide_all' => false,
            'show_all' => true,
            default => $this->show_student_names,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
