<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Support\PwaIcons;
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
        $settings = SiteSetting::current();
        $name = $settings->name ?: config('app.name', 'Consultation');
        return response(json_encode([
            'id' => $admin ? '/admin/' : '/', 'name' => $name.($admin ? ' Admin' : ''),
            'short_name' => $admin ? mb_substr($name, 0, 18).' Admin' : mb_substr($name, 0, 24),
            'description' => $admin ? 'School consultation administration and account management.' : 'School consultation schedules and booking requests.',
            'start_url' => $admin ? '/admin/' : '/consultations', 'scope' => $admin ? '/admin/' : '/', 'display' => 'standalone',
            'background_color' => '#f8fafc', 'theme_color' => '#be123c',
            'prefer_related_applications' => false,
            'related_applications' => [[
                'platform' => 'webapp',
                'url' => route($admin ? 'pwa.admin-manifest' : 'pwa.manifest'),
                'id' => rtrim(url('/'), '/').($admin ? '/admin/' : '/'),
            ]],
            'icons' => [
                ['src' => PwaIcons::url(192, $settings->logo_path), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => PwaIcons::url(512, $settings->logo_path), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/manifest+json', 'Cache-Control' => 'no-cache',
        ]);
    }

    public function icon(int $size): Response
    {
        abort_unless(in_array($size, [180, 192, 512], true), 404);

        return response(PwaIcons::png($size, SiteSetting::current()->logo_path), 200, [
            'Content-Type' => 'image/png', 'Cache-Control' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
