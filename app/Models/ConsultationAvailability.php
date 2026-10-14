<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsultationAvailability extends Model
{
    protected $fillable = ['teacher_id', 'date', 'starts_at', 'ends_at', 'slot_minutes', 'location', 'cancelled_at'];

    protected function casts(): array
    {
        return ['date' => 'date', 'slot_minutes' => 'integer', 'cancelled_at' => 'datetime'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ConsultationAppointment::class, 'availability_id');
    }
}
