# Uploaded images on InfinityFree / shared hosting

For PWA installation, keep `crossorigin="use-credentials"` on the manifest link.
InfinityFree's browser-check layer can return JavaScript challenge HTML instead
of the manifest to requests without its security cookie. A manifest cannot run
that JavaScript. The credentialed link allows the browser to send the same-origin
cookie after the page's security check. This applies to both portal and admin
manifests. It does not bypass security or require exposing any cookies in code.

Uploaded logos, favicons, and profile photos live in `storage/app/public`.
Normally Laravel exposes them through the `public/storage` symlink, but shared
hosting can omit or block that link. This app also provides a PHP image route
that does not require symlinks.

## Hosting setup

1. Upload the current application code, including `PublicImageController.php`,
   `routes/web.php`, the updated views, and the `public/.htaccess` file.
   The domain's public document root must contain the contents of Laravel's
   `public` directory; do not expose the project root or `.env` to the web.
2. In the **hosted** `.env`, use your real HTTPS domain and the image route:

   ```dotenv
   APP_URL=https://your-school-domain.example
   PUBLIC_STORAGE_URL=/media
   ```

   Do not use `localhost`, a Windows path, or a copied local `ASSET_URL`.
   Leave `ASSET_URL` unset unless you deliberately host static assets elsewhere.
3. Preserve/copy the uploaded files into the hosted app's `storage/app/public`,
   keeping their subfolders (`logos`, `favicons`, `teacher-profiles`, and
   `student-profiles`). Importing SQL does **not** transfer image files. If the
   original files are unavailable, upload replacements through the app.
4. Ensure PHP can read these folders and write new uploads. Use your host's
   recommended permissions; do not make the entire project world-writable.
5. Clear stale Laravel caches where a terminal is available:

   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

   If there is no terminal, do not deploy generated local cache files from
   `bootstrap/cache` (such as `config.php` or route caches). Remove only stale
   generated config/route caches via the host's file manager, retaining `.gitignore`
   and the directory. Never create a public web endpoint to run Artisan commands.
6. Open `/media/logos/<actual-upload-filename>.png` on your hosted domain. A valid
   existing image should load. Use the filename stored in your database or shown
   in the browser's image URL, not this placeholder. Hard-refresh the app afterward.

The default `/storage/...` URLs also have a Laravel fallback when requests reach
PHP. `/media/...` avoids hosts rejecting an existing but unsupported storage
symlink before Laravel can handle it. No storage link or database migration is
needed for the `/media` route.

## If an image still does not load

- **404:** check the file exists on the host with exactly the stored name and
  letter case. Confirm routes/config are current. Linux filenames are case-sensitive.
- **403:** check host file permissions and whether its security layer blocks PHP
  asset requests. Try the `/media` route rather than the storage symlink.
- **500:** inspect `storage/logs/laravel.log` privately for read/access errors.
- **Wrong domain or HTTP URL:** check `APP_URL`, `ASSET_URL`, `PUBLIC_STORAGE_URL`,
  and stale configuration caches. A relative `/media` URL follows the current host.
- **Static icons/CSS also missing:** verify that `public/pwa`, `public/css`, and
  `public/js` were uploaded into the actual public document root.

Only allowed image types in the four public upload folders are served. Arbitrary
storage files, HTML, SVG, PHP, logs, and private files are not exposed by this route.
