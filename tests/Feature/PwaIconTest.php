<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Support\PwaIcons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PwaIconTest extends TestCase
{
    use RefreshDatabase;

    private function logo(string $filename = 'school.png'): string
    {
        $image = imagecreatetruecolor(300, 150);
        imagefill($image, 0, 0, imagecolorallocate($image, 210, 20, 40));
        ob_start();
        imagepng($image);
        Storage::disk('public')->put('logos/'.$filename, ob_get_clean());
        imagedestroy($image);

        return 'logos/'.$filename;
    }

    public function test_both_manifests_and_iphone_icon_use_settings_logo_with_versioned_urls(): void
    {
        Storage::fake('public');
        $settings = SiteSetting::current();
        $settings->update(['logo_path' => $this->logo()]);
        foreach (['/manifest.webmanifest', '/admin/manifest.webmanifest'] as $url) {
            $this->get($url)->assertOk()->assertJsonPath('icons.0.src', PwaIcons::url(192, $settings->logo_path))
                ->assertJsonPath('icons.1.src', PwaIcons::url(512, $settings->logo_path))
                ->assertJsonPath('icons.0.type', 'image/png');
        }
        foreach (['/portal/login', '/admin/login'] as $url) {
            $this->get($url)->assertOk()->assertSee('rel="apple-touch-icon" href="'.PwaIcons::url(180, $settings->logo_path).'"', false);
        }
        $oldUrl = PwaIcons::url(192, $settings->logo_path);
        $settings->update(['logo_path' => $this->logo('replacement.png')]);
        $this->assertNotSame($oldUrl, PwaIcons::url(192, $settings->logo_path));
    }

    public function test_generated_icons_have_correct_dimensions_and_preserve_logo_without_cropping(): void
    {
        Storage::fake('public');
        SiteSetting::current()->update(['logo_path' => $this->logo()]);
        foreach ([180, 192, 512] as $size) {
            $response = $this->get(PwaIcons::url($size, 'logos/school.png'))->assertOk()->assertHeader('Content-Type', 'image/png');
            $dimensions = getimagesizefromstring($response->getContent());
            $this->assertSame($size, $dimensions[0]);
            $this->assertSame($size, $dimensions[1]);
            $image = imagecreatefromstring($response->getContent());
            $this->assertSame(0xffffff, imagecolorat($image, 0, 0));
            $this->assertSame((210 << 16) | (20 << 8) | 40, imagecolorat($image, (int) ($size / 2), (int) ($size / 2)));
            imagedestroy($image);
        }
        $this->get('/pwa/school-icon-999.png')->assertNotFound();
    }

    public function test_missing_or_removed_logo_safely_falls_back_to_default_icons(): void
    {
        Storage::fake('public');
        $settings = SiteSetting::current();
        $settings->update(['logo_path' => 'logos/missing.png']);
        $response = $this->get('/pwa/school-icon-512.png')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertSame(file_get_contents(public_path('pwa/icon-512.png')), $response->getContent());
        $settings->update(['logo_path' => null]);
        $this->get('/manifest.webmanifest')->assertOk()->assertJsonPath('icons.0.src', '/pwa/icon-192.png');
    }
}
