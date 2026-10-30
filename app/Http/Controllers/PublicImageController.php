<?php

namespace App\Http\Controllers;

use App\Support\SchoolImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicImageController extends Controller
{
    public function show(string $directory, string $filename): BinaryFileResponse|StreamedResponse
    {
        // Only serve the app's public image folders, never arbitrary storage files.
        abort_unless(in_array($directory, ['logos', 'favicons', 'teacher-profiles', 'student-profiles'], true), 404);
        abort_unless(preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]*\.(?:jpe?g|png|webp|gif|ico)\z/i', $filename) === 1, 404);
        $path = $directory.'/'.$filename;
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);
        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'], true), 404);

        // Legacy logos were stored at full camera/brand resolution (sometimes
        // >1 MB). Shrink them once so later requests serve a small file.
        if (in_array($directory, ['logos', 'favicons'], true)) {
            try {
                SchoolImage::shrinkStored($path, $directory === 'logos' ? 512 : 192);
            } catch (\Throwable) {
                // Read-only or unsupported storage: still serve the original.
            }
        }

        // Uploaded filenames are unique per upload, so brand images can be
        // cached indefinitely. Profile photos stay in the private browser cache.
        $headers = [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => in_array($directory, ['logos', 'favicons'], true)
                ? 'public, max-age=31536000, immutable'
                : 'private, max-age=86400',
        ];

        if (config('filesystems.disks.public.driver') === 'local') {
            // Construct directly: Laravel's response()->file() forces a "public"
            // directive, which would override the private profile-photo cache.
            $response = new BinaryFileResponse($disk->path($path), 200, $headers, false);
            $response->setContentDisposition('inline');

            return $response;
        }

        return $disk->response($path, null, $headers);
    }
}
