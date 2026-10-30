<?php

use App\Http\Controllers\PublicImageController;
use App\Http\Controllers\PwaController;
use Illuminate\Support\Facades\Route;

// Public bytes: uploads, manifests, and generated PWA icons. Registering them
// without the web/session middleware keeps them out of PHP sessions and avoids
// writing a session cookie on every logo, icon, and avatar request.
Route::get('/storage/{directory}/{filename}', [PublicImageController::class, 'show'])->name('images.storage');
Route::get('/media/{directory}/{filename}', [PublicImageController::class, 'show'])->name('images.media');
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/admin/manifest.webmanifest', [PwaController::class, 'adminManifest'])->name('pwa.admin-manifest');
Route::get('/pwa/school-icon-{size}.png', [PwaController::class, 'icon'])->whereIn('size', ['180', '192', '512'])->name('pwa.icon');
