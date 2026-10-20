<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_uploads_are_served_without_a_storage_link(): void
    {
        Storage::fake('public');
        foreach (['logos', 'favicons', 'teacher-profiles', 'student-profiles'] as $folder) {
            $path = UploadedFile::fake()->image('school.png')->storeAs($folder, 'school.png', 'public');
            foreach (['storage', 'media'] as $prefix) {
                $response = $this->get('/'.$prefix.'/'.$path)->assertOk()->assertHeader('Content-Type', 'image/png')
                    ->assertHeader('X-Content-Type-Options', 'nosniff');
                $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
                $this->assertSame(Storage::disk('public')->get($path), $response->streamedContent());
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
            $this->get($url)->assertOk()->assertSee('src="/media/logos/school.png"', false)->assertDontSee('/storage/logos/', false);
        }
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.settings'))->assertOk()
            ->assertSee('src="/media/logos/school.png"', false)->assertSee('/media/favicons/school.png', false);
    }
}
