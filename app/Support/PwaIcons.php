<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PwaIcons
{
    public static function url(int $size, ?string $logoPath): string
    {
        if (! $logoPath) return '/pwa/icon-'.($size === 180 ? 192 : $size).'.png';

        // Uploads receive a new filename, so changing the logo changes the icon URL.
        return route('pwa.icon', ['size' => $size, 'v' => substr(hash('sha256', $logoPath), 0, 16)], false);
    }

    public static function png(int $size, ?string $logoPath): string
    {
        $fallback = file_get_contents(public_path('pwa/icon-'.($size === 512 ? 512 : 192).'.png'));
        if (! $logoPath || ! function_exists('imagecreatefromstring')) return $fallback;
        // The image source is the configured school logo, never a client-supplied path.
        if (! preg_match('#\Alogos/[a-zA-Z0-9][a-zA-Z0-9._-]*\.(?:jpe?g|png|webp|gif)\z#i', $logoPath)) return $fallback;
        $disk = Storage::disk('public');
        if (! $disk->exists($logoPath)) return $fallback;
        $bytes = $disk->get($logoPath);
        $dimensions = @getimagesizefromstring($bytes);
        // Avoid decoding an unexpectedly large image on memory-limited shared hosts.
        if (! $dimensions || $dimensions[0] * $dimensions[1] > 4_000_000) return $fallback;
        $source = @imagecreatefromstring($bytes);
        if (! $source) return $fallback;
        $canvas = imagecreatetruecolor($size, $size);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        $scale = ($size * 0.8) / max($dimensions[0], $dimensions[1]);
        $width = max(1, (int) round($dimensions[0] * $scale));
        $height = max(1, (int) round($dimensions[1] * $scale));
        imagecopyresampled($canvas, $source, (int) (($size - $width) / 2), (int) (($size - $height) / 2), 0, 0, $width, $height, $dimensions[0], $dimensions[1]);
        ob_start();
        imagepng($canvas);
        $png = ob_get_clean();
        imagedestroy($source);
        imagedestroy($canvas);

        return $png;
    }
}
