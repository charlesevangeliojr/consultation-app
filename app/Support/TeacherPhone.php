<?php

namespace App\Support;

class TeacherPhone
{
    public static function split(?string $number, ?string $country): array
    {
        $countries = config('phone_countries');
        $digits = preg_replace('/\D/', '', $number ?? '');
        if (! isset($countries[$country])) {
            $country = 'PH';
            $ordered = $countries;
            uasort($ordered, fn ($a, $b) => strlen($b['code']) <=> strlen($a['code']));
            foreach ($ordered as $key => $option) {
                if ($digits !== '' && str_starts_with($digits, $option['code'])) {
                    $country = $key;
                    break;
                }
            }
        }
        $code = $countries[$country]['code'];

        return ['country' => $country, 'number' => str_starts_with($digits, $code) ? substr($digits, strlen($code)) : $digits];
    }
}
