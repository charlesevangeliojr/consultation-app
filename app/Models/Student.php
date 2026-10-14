<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'contact_number',
        'birthday',
        'gender',
        'address',
        'status',
        'lrn',
        'profile_photo_path',
        'grade_level_id',
        'section_id',
    ];

    protected function casts(): array
    {
        return ['birthday' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
