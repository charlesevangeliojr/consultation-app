<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoginBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_pages_use_database_branding_instead_of_hard_coded_or_environment_names(): void
    {
        config(['app.name' => 'Environment Brand']);
        SiteSetting::current()->update(['name' => 'Riverside School', 'logo_path' => 'logos/school.png', 'favicon_path' => 'favicons/school.png']);

        foreach (['portal.login' => 'Sign in', 'admin.login' => 'Admin login'] as $route => $title) {
            $this->get(route($route))->assertOk()
                ->assertSee('<title>'.$title.' · Riverside School</title>', false)
                ->assertSee('data-login-brand', false)
                ->assertSee('src="/storage/logos/school.png"', false)
                ->assertSee('href="/storage/favicons/school.png"', false)
                ->assertDontSee('ConSync')->assertDontSee('Environment Brand');
        }
    }

    public function test_admin_branding_edits_appear_on_both_login_pages(): void
    {
        Storage::fake('public');
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->from(route('admin.settings'))->put(route('admin.settings.update'), [
            'name' => 'Updated School',
            'logo' => UploadedFile::fake()->image('school.png'),
            'favicon' => UploadedFile::fake()->image('favicon.png'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.settings'));
        $settings = SiteSetting::current();
        Storage::disk('public')->assertExists($settings->logo_path);
        Storage::disk('public')->assertExists($settings->favicon_path);
        $this->post(route('admin.logout'))->assertRedirect();

        foreach (['portal.login', 'admin.login'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Updated School')
                ->assertSee(Storage::disk('public')->url($settings->logo_path))
                ->assertSee(Storage::disk('public')->url($settings->favicon_path));
        }
    }

    public function test_school_name_is_escaped_and_missing_images_have_a_fallback(): void
    {
        SiteSetting::current()->update(['name' => '<script>alert("school")</script>', 'logo_path' => null, 'favicon_path' => null]);
        foreach (['portal.login', 'admin.login'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('<script>alert("school")</script>')
                ->assertDontSee('<script>alert("school")</script>', false)
                ->assertDontSee('rel="icon"', false)
                ->assertSee('data-login-brand', false);
        }
    }
}
