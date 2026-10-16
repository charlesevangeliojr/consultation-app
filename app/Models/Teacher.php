<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Teacher extends Model
{
    protected $fillable = ['user_id', 'consultation_approved', 'slot_minutes', 'auto_approve_bookings', 'show_student_names', 'contact_number', 'birthday', 'gender', 'address', 'profile_photo_path', 'phone_country', 'house_number', 'region', 'province', 'city', 'barangay', 'postal_code', 'consultation_offerings'];

    protected function casts(): array
    {
        return ['consultation_approved' => 'boolean', 'slot_minutes' => 'integer', 'auto_approve_bookings' => 'boolean', 'show_student_names' => 'boolean', 'birthday' => 'date'];
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    public static function profileDetailRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'contact_number' => ['required', 'string', 'regex:/^\+[1-9][0-9]{6,14}$/'],
            'phone_country' => ['required', Rule::in(array_keys(config('phone_countries')))],
            'birthday' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'region' => ['required', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:150'],
            'barangay' => ['required', 'string', 'max:150'],
            'house_number' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'consultation_offerings' => ['required', 'string', 'max:2000'],
        ];
    }

    public function missingProfileFields(): array
    {
        $labels = static::profileFieldLabels();
        $values = $this->attributesToArray();
        $values['name'] = $this->user->name;
        $values['email'] = $this->user->email;
        $values['birthday'] = $this->birthday?->toDateString();
        $validator = Validator::make($values, static::profileDetailRules());
        $validator->fails();
        $invalid = array_keys($validator->failed());
        if (in_array('phone_country', $invalid)) $invalid[] = 'contact_number';
        $country = config('phone_countries.'.$this->phone_country);
        if (is_array($country) && (! str_starts_with($this->contact_number ?? '', '+'.$country['code']) ||
            ! preg_match('/^'.$country['pattern'].'$/', substr($this->contact_number ?? '', strlen($country['code']) + 1)))) {
            $invalid[] = 'contact_number';
        }
        if (array_intersect($invalid, ['region', 'city', 'barangay'])) $invalid[] = 'address';
        if (blank($this->profile_photo_path)) $invalid[] = 'profile_photo';

        return array_intersect_key($labels, array_flip($invalid));
    }

    public static function profileFieldLabels(): array
    {
        return ['name' => 'Full name', 'email' => 'Email', 'contact_number' => 'Contact number', 'birthday' => 'Birthday', 'gender' => 'Sex', 'address' => 'Address', 'profile_photo' => 'Profile picture', 'consultation_offerings' => 'What you offer'];
    }

    public function hasCompleteProfile(): bool
    {
        return $this->missingProfileFields() === [];
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
