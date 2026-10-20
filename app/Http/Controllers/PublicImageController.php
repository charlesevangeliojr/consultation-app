<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicImageController extends Controller
{
    public function show(string $directory, string $filename): StreamedResponse
    {
        // Only serve the app's public image folders, never arbitrary storage files.
        abort_unless(in_array($directory, ['logos', 'favicons', 'teacher-profiles', 'student-profiles'], true), 404);
        abort_unless(preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]*\.(?:jpe?g|png|webp|gif|ico)\z/i', $filename) === 1, 404);
        $path = $directory.'/'.$filename;
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);
        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'], true), 404);

        return $disk->response($path, null, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
