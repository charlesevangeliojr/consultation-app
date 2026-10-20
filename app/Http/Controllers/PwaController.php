<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Response;

class PwaController extends Controller
{
    public function manifest(): Response
    {
        return $this->appManifest(false);
    }

    public function adminManifest(): Response
    {
        return $this->appManifest(true);
    }

    private function appManifest(bool $admin): Response
    {
        $name = SiteSetting::current()->name ?: config('app.name', 'Consultation');
        return response(json_encode([
            'id' => $admin ? '/admin/' : '/', 'name' => $name.($admin ? ' Admin' : ''),
            'short_name' => $admin ? mb_substr($name, 0, 18).' Admin' : mb_substr($name, 0, 24),
            'description' => $admin ? 'School consultation administration and account management.' : 'School consultation schedules and booking requests.',
            'start_url' => $admin ? '/admin/' : '/consultations', 'scope' => $admin ? '/admin/' : '/', 'display' => 'standalone',
            'background_color' => '#f8fafc', 'theme_color' => '#be123c',
            'icons' => [
                ['src' => '/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => '/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/manifest+json', 'Cache-Control' => 'no-cache',
        ]);
    }
}
