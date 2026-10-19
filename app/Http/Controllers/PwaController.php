<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Response;

class PwaController extends Controller
{
    public function manifest(): Response
    {
        $name = SiteSetting::current()->name ?: config('app.name', 'Consultation');
        return response(json_encode([
            'id' => '/', 'name' => $name, 'short_name' => mb_substr($name, 0, 24),
            'description' => 'School consultation schedules and booking requests.',
            'start_url' => '/consultations', 'scope' => '/', 'display' => 'standalone',
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
