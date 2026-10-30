<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolImage
{
    private const RESIZABLE = ['image/jpeg', 'image/png', 'image/webp'];

    private const MAX_PIXELS = 16_000_000;

    /**
     * Store an uploaded brand image, shrinking oversized originals first so a
     * 1254x1254 logo is not downloaded to render a 40x40 mark on every page.
     */
    public static function store(UploadedFile $file, string $directory, int $maxDimension): string
    {
        $mime = (string) $file->getMimeType();
        $bytes = @file_get_contents($file->getRealPath());
        $resized = $bytes === false ? null : self::resize($bytes, $mime, $maxDimension);

        if ($resized === null) {
            return $file->store($directory, 'public');
        }

        $path = $directory.'/'.Str::random(40).'.'.self::extension($mime);
        Storage::disk('public')->put($path, $resized);

        return $path;
    }

    /**
     * Shrink an already-stored logo/favicon in place. Runs once per legacy
     * upload after deployment; subsequent requests serve the smaller file.
     */
    public static function shrinkStored(string $path, int $maxDimension): void
    {
        if (! function_exists('imagecreatefromstring')) return;

        $disk = Storage::disk('public');
        $mime = (string) $disk->mimeType($path);
        if (! in_array($mime, self::RESIZABLE, true)) return;

        $bytes = $disk->get($path);
        $dimensions = @getimagesizefromstring($bytes);
        if (! $dimensions) return;
        if (max($dimensions[0], $dimensions[1]) <= $maxDimension && strlen($bytes) <= 150 * 1024) return;

        $encoded = self::resize($bytes, $mime, $maxDimension);
        if ($encoded === null) return;
        $withinSize = max($dimensions[0], $dimensions[1]) <= $maxDimension;
        // Already the right dimensions and no meaningful byte saving: stop
        // re-encoding this file on every request.
        if ($withinSize && strlen($encoded) >= strlen($bytes) * 0.9) return;
        // Resampling can enlarge tiny flat artwork slightly; the much smaller
        // pixel count still helps. Skip only a pathological size increase.
        if (strlen($encoded) > max(strlen($bytes) * 1.5, 250 * 1024)) return;

        $disk->put($path, $encoded);
    }

    private static function resize(string $bytes, string $mime, int $maxDimension): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! in_array($mime, self::RESIZABLE, true)) return null;

        $dimensions = @getimagesizefromstring($bytes);
        if (! $dimensions) return null;
        [$width, $height] = $dimensions;
        if ($width * $height > self::MAX_PIXELS) return null;

        $source = @imagecreatefromstring($bytes);
        if (! $source) return null;

        $scale = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        } else {
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        $encoded = match ($mime) {
            'image/png' => imagepng($canvas, null, 9),
            'image/webp' => imagewebp($canvas, null, 82),
            default => imagejpeg($canvas, null, 85),
        };
        $output = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        return $encoded === false || $output === '' ? null : $output;
    }

    private static function extension(string $mime): string
    {
        return match ($mime) {
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            default => 'png',
        };
    }
}
