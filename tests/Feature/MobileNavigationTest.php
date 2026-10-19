<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNavigationTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function navigation(string $html): string
    {
        $this->assertSame(1, preg_match('/<nav class="mobile-bottom-navigation".*?<\/nav>/s', $html, $matches));
        return $matches[0];
    }

    public function test_student_has_bottom_navigation_with_active_page_and_no_hamburger(): void
    {
        $role = Role::firstOrCreate(['name' => 'Student'], ['is_super_admin' => false]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $response = $this->actingAs($user)->get(route('consultations.dashboard'));
        $response->assertOk()->assertSee('mobile-navigation.css')->assertDontSee('student-mobile-menu-button')
            ->assertSee('data-mobile-account-menu', false)->assertSee('Open account menu')->assertSee('mobile-account-menu.js');
        $navigation = $this->navigation($response->getContent());
        $this->assertStringContainsString('Student mobile navigation', $navigation);
        foreach (['Dashboard', 'Calendar', 'My profile', 'aria-current="page"'] as $text) $this->assertStringContainsString($text, $navigation);
        $this->assertStringNotContainsString('Monitor', $navigation);
    }

    public function test_teacher_bottom_navigation_links_to_separate_workspace_pages(): void
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $teacher = User::factory()->create(['role_id' => $role->id]);
        $this->createCompleteTeacherProfile($teacher, ['consultation_approved' => true]);
        $response = $this->actingAs($teacher)->get(route('consultations.teacher'));
        $response->assertOk()->assertSee('teacher-calendar.css')
            ->assertSee('data-mobile-account-menu', false)->assertSee('Open account menu')->assertSee('mobile-account-menu.js');
        $navigation = $this->navigation($response->getContent());
        foreach (['teacher', 'teacher.requests', 'teacher.availability', 'teacher.preferences', 'teacher-profile.edit'] as $route) {
            $this->assertStringContainsString(route('consultations.'.$route), $navigation);
        }
        $this->assertSame(1, substr_count($navigation, 'aria-current="page"'));
        $this->assertStringNotContainsString('data-navigation-section', $navigation);
        $this->assertStringContainsString('Teacher mobile navigation', $navigation);
        $this->assertStringNotContainsString('Accounts', $navigation);
    }

    public function test_teacher_without_approval_can_manage_availability_but_cannot_publish(): void
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $teacher = User::factory()->create(['role_id' => $role->id]);
        $this->createCompleteTeacherProfile($teacher);
        $navigation = $this->navigation($this->actingAs($teacher)->get(route('consultations.teacher'))->assertOk()->getContent());
        $this->assertStringContainsString(route('consultations.teacher.availability'), $navigation);
        $this->assertStringContainsString(route('consultations.teacher.preferences'), $navigation);
        $this->get(route('consultations.teacher.availability'))->assertOk()
            ->assertDontSee('id="teacher-publish"', false)->assertSee('cannot publish availability');
    }

    public function test_super_admin_has_all_five_bottom_navigation_destinations(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $navigation = $this->navigation($this->actingAs($admin)->get(route('admin.school'))->assertOk()->getContent());
        foreach (['Dashboard', 'Accounts', 'Grades &amp; sections', 'Consultations', 'Settings', 'Admin mobile navigation'] as $text) {
            $this->assertStringContainsString($text, $navigation);
        }
        $this->assertStringContainsString('aria-current="page"', $navigation);
    }

    public function test_admin_bottom_navigation_respects_module_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->syncPermissions(['settings' => ['view' => true]]);
        $navigation = $this->navigation($this->actingAs($admin)->get(route('admin.settings'))->assertOk()->getContent());
        foreach (['Grades &amp; sections', 'Settings'] as $text) $this->assertStringContainsString($text, $navigation);
        foreach (['Dashboard', 'Accounts', 'Monitor'] as $text) $this->assertStringNotContainsString($text, $navigation);
    }
}
