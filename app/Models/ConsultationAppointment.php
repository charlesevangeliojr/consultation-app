<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationAppointment extends Model
{
    protected $fillable = ['availability_id', 'teacher_id', 'student_id', 'starts_at', 'ends_at', 'topic', 'status'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(ConsultationAvailability::class, 'availability_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
