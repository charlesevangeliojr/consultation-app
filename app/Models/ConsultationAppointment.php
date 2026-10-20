<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationAppointment extends Model
{
    protected $fillable = ['availability_id', 'teacher_id', 'student_id', 'starts_at', 'ends_at', 'topic', 'status', 'is_proposed', 'rejection_reason'];

    protected $attributes = ['is_proposed' => false];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_proposed' => 'boolean', 'student_rejection_seen_at' => 'datetime'];
    }

    public function scopeReservesTeacherTime(Builder $query): void
    {
        $query->whereNotIn('status', ['cancelled', 'rejected'])
            ->where(fn ($query) => $query->where('is_proposed', false)->orWhere('status', '!=', 'pending'));
    }

    public function scopeNeedsStudentAttention(Builder $query): void
    {
        $query->where(fn ($items) => $items
            ->where(fn ($active) => $active->whereIn('status', ['pending', 'confirmed'])->where('ends_at', '>', now()))
            ->orWhere(fn ($rejected) => $rejected->where('status', 'rejected')->whereNull('student_rejection_seen_at')));
    }

    public function rejectionMessage(): ?string
    {
        return match ($this->rejection_reason) {
            'expired' => 'Automatically rejected because the requested start time arrived before teacher approval.',
            'time_taken' => 'Automatically rejected because another accepted consultation took this time.',
            default => null,
        };
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
