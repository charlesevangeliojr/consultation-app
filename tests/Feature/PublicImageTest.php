<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class PublicImageTest extends TestCase
{
    use RefreshDatabase;

    private function body(TestResponse $response): string
    {
        $base = $response->baseResponse;

        return $base instanceof BinaryFileResponse
            ? (string) file_get_contents($base->getFile()->getPathname())
            : $response->streamedContent();
    }

    private function largeLogoPng(int $size = 1200): string
    {
        $image = imagecreatetruecolor($size, $size);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        foreach ([[0.5, 0.5, 0.9, [190, 18, 60]], [0.35, 0.4, 0.3, [251, 113, 133]], [0.7, 0.65, 0.25, [15, 23, 42]]] as [$cx, $cy, $r, $color]) {
            imagefilledellipse($image, (int) ($size * $cx), (int) ($size * $cy), (int) ($size * $r), (int) ($size * $r), imagecolorallocate($image, ...$color));
        }
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    public function test_public_uploads_are_served_without_a_storage_link(): void
    {
        Storage::fake('public');
        foreach (['logos', 'favicons', 'teacher-profiles', 'student-profiles'] as $folder) {
            $path = UploadedFile::fake()->image('school.png')->storeAs($folder, 'school.png', 'public');
            foreach (['storage', 'media'] as $prefix) {
                $response = $this->get('/'.$prefix.'/'.$path)->assertOk()->assertHeader('Content-Type', 'image/png')
                    ->assertHeader('X-Content-Type-Options', 'nosniff');
                $cache = (string) $response->headers->get('Cache-Control');
                if (in_array($folder, ['logos', 'favicons'], true)) {
                    $this->assertStringContainsString('public', $cache);
                    $this->assertStringContainsString('immutable', $cache);
                } else {
                    $this->assertStringContainsString('private', $cache);
                    $this->assertStringNotContainsString('immutable', $cache);
                }
                $this->assertSame(Storage::disk('public')->get($path), $this->body($response));
            }
        }
    }

    public function test_image_routes_cannot_expose_non_images_private_folders_or_missing_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/fake.png', '<html>not an image</html>');
        Storage::disk('public')->put('logos/execute.php', '<?php echo "private";');
        UploadedFile::fake()->image('private.png')->storeAs('backups', 'private.png', 'public');
        foreach (['/media/logos/fake.png', '/media/logos/execute.php', '/media/backups/private.png', '/media/logos/missing.png', '/media/logos/../private.png', '/storage/private/secrets.txt'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_branding_urls_honor_media_override_in_public_and_admin_views(): void
    {
        config(['filesystems.disks.public.url' => '/media']);
        Storage::fake('public', ['url' => '/media']);
        SiteSetting::current()->update(['logo_path' => 'logos/school.png', 'favicon_path' => 'favicons/school.png']);
        foreach (['/', '/register', '/portal/login', '/admin/login'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('src="/media/logos/school.png"', false)
                ->assertSee('rel="preload" as="image"', false)
                ->assertDontSee('/storage/logos/', false);
        }
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.settings'))->assertOk()
            ->assertSee('src="/media/logos/school.png"', false)->assertSee('/media/favicons/school.png', false);
    }

    public function test_oversized_legacy_logos_are_shrunk_once_when_served(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/legacy.png', $this->largeLogoPng());
        $original = Storage::disk('public')->get('logos/legacy.png');
        $this->get('/media/logos/legacy.png')->assertOk();

        $optimized = Storage::disk('public')->get('logos/legacy.png');
        $dimensions = getimagesizefromstring($optimized);
        $this->assertLessThanOrEqual(512, max($dimensions[0], $dimensions[1]));
        $this->assertLessThan(1200 * 1200, $dimensions[0] * $dimensions[1]);
        $this->assertLessThan(strlen($original) * 1.5, strlen($optimized));
    }

    public function test_uploaded_logos_are_downscaled_before_being_stored(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('admin.settings.update'), [
                'name' => 'Farsync',
                'logo' => UploadedFile::fake()->image('brand.png', 1200, 1200),
            ])->assertRedirect();

        $path = SiteSetting::current()->logo_path;
        $this->assertNotNull($path);
        $dimensions = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertLessThanOrEqual(512, max($dimensions[0], $dimensions[1]));
        $this->assertStringEndsWith('.png', $path);
    }
}
