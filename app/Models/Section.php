<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $fillable = ['name', 'grade_level_id'];

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }
}
