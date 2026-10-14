<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['name', 'logo_path', 'favicon_path', 'queue_name_visibility'];

    protected function casts(): array
    {
        return [];
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'name' => config('app.name', 'Consultation'),
        ]);
    }
}
