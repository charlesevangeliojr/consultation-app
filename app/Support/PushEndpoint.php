<?php

namespace App\Support;

class PushEndpoint
{
    public static function allowed(string $endpoint): bool
    {
        if (! filter_var($endpoint, FILTER_VALIDATE_URL)) return false;
        $url = parse_url($endpoint);
        if (($url['scheme'] ?? '') !== 'https' || isset($url['user']) || isset($url['pass']) || isset($url['fragment']) || ($url['port'] ?? 443) !== 443) return false;
        $host = strtolower($url['host'] ?? '');
        foreach (config('webpush.endpoint_hosts', []) as $allowed) {
            if ($host === $allowed || (str_starts_with($allowed, '.') && str_ends_with($host, $allowed))) return true;
        }
        return false;
    }
}
