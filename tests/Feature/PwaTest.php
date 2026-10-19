<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_public_and_uses_school_name_without_account_data(): void
    {
        SiteSetting::current()->update(['name' => 'Fictional School']);
        $this->get('/manifest.webmanifest')->assertOk()->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'Fictional School')->assertJsonPath('start_url', '/consultations')
            ->assertJsonPath('scope', '/')->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.0.sizes', '192x192')->assertJsonPath('icons.1.sizes', '512x512');
        foreach ([192, 512] as $size) {
            $dimensions = getimagesize(public_path("pwa/icon-{$size}.png"));
            $this->assertSame($size, $dimensions[0]);
            $this->assertSame($size, $dimensions[1]);
            $this->assertSame('image/png', $dimensions['mime']);
        }
    }

    public function test_public_and_authenticated_pages_have_install_ui_and_standalone_loading(): void
    {
        foreach (['/', '/portal/login', '/admin/login', '/register', '/register?role=student'] as $url) {
            $this->get($url)->assertOk()->assertSee('manifest.webmanifest')->assertSee('js/pwa.js')->assertSee('css/pwa.css')
                ->assertSee('data-pwa-install', false)->assertSee('data-pwa-launch', false)->assertSee('data-pwa-instructions', false);
            $this->get($url)->assertSee('Your familiar workspace')->assertSee('Not now')->assertSee('No APK download. Internet connection required.');
        }
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.accounts'))->assertOk()
            ->assertSee('data-pwa-install', false)->assertSee('data-pwa-launch', false);
    }
}
