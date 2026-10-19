<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    public function test_public_pages_include_shared_theme_assets_and_accessible_toggle(): void
    {
        foreach (['/', '/admin/login', '/portal/login', '/register', '/register?role=student', '/register?role=teacher'] as $url) {
            $this->get($url)->assertOk()->assertSee('js/theme.js')->assertSee('css/theme.css')
                ->assertSee('data-theme-toggle', false)->assertSee('aria-label="Switch to light mode"', false);
        }
    }

    public function test_every_role_has_a_single_theme_control_in_the_top_navbar(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $student = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        $teacher = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Teacher'])->id]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => true]);
        foreach ([[$admin, 'admin.accounts'], [$student, 'consultations.dashboard'], [$teacher, 'consultations.teacher']] as [$user, $route]) {
            $response = $this->actingAs($user)->get(route($route))->assertOk()->assertSee('js/theme.js')->assertSee('css/theme.css')
                ->assertSee('data-theme-toggle', false)->assertSee('data-theme-label', false);
            $this->assertSame(1, substr_count($response->getContent(), 'data-theme-toggle'));
            preg_match('/<header class="mobile-topbar[\s\S]*?<\/header>/', $response->getContent(), $header);
            $this->assertStringContainsString('data-theme-toggle', $header[0]);
        }
    }
}
