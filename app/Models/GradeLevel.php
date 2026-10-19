<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeLevel extends Model
{
    public const STANDARD_NAMES = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

    protected $fillable = ['name', 'registration_enabled'];

    protected function casts(): array
    {
        return ['registration_enabled' => 'boolean'];
    }

    public function scopeForRegistration(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereIn('name', self::STANDARD_NAMES)->where('registration_enabled', true);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
