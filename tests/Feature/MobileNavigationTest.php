<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNavigationTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertOk()->assertSee('mobile-navigation.css')->assertDontSee('student-mobile-menu-button');
        $navigation = $this->navigation($response->getContent());
        $this->assertStringContainsString('Student mobile navigation', $navigation);
        foreach (['Dashboard', 'Calendar', 'My profile', 'aria-current="page"'] as $text) $this->assertStringContainsString($text, $navigation);
        $this->assertStringNotContainsString('Monitor', $navigation);
    }

    public function test_teacher_bottom_navigation_links_to_calendar_sections(): void
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $teacher = User::factory()->create(['role_id' => $role->id]);
        $teacher->teacher()->create(['consultation_approved' => true]);
        $response = $this->actingAs($teacher)->get(route('consultations.teacher'));
        $response->assertOk()->assertSee('teacher-calendar.css');
        $navigation = $this->navigation($response->getContent());
        foreach (['teacher-month-calendar', 'teacher-requests', 'teacher-publish', 'teacher-settings'] as $id) {
            $this->assertStringContainsString('#'.$id, $navigation);
            $response->assertSee('id="'.$id.'"', false);
        }
        $this->assertStringContainsString('Teacher mobile navigation', $navigation);
        $this->assertStringNotContainsString('Accounts', $navigation);
    }

    public function test_teacher_without_approval_has_no_publish_navigation(): void
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $teacher = User::factory()->create(['role_id' => $role->id]);
        $navigation = $this->navigation($this->actingAs($teacher)->get(route('consultations.teacher'))->assertOk()->getContent());
        $this->assertStringNotContainsString('#teacher-publish', $navigation);
        $this->assertStringContainsString('#teacher-settings', $navigation);
    }

    public function test_super_admin_has_all_five_bottom_navigation_destinations(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $navigation = $this->navigation($this->actingAs($admin)->get(route('admin.school'))->assertOk()->getContent());
        foreach (['Dashboard', 'Accounts', 'School', 'Monitor', 'Settings', 'Admin mobile navigation'] as $text) {
            $this->assertStringContainsString($text, $navigation);
        }
        $this->assertStringContainsString('aria-current="page"', $navigation);
    }

    public function test_admin_bottom_navigation_respects_module_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->syncPermissions(['settings' => ['view' => true]]);
        $navigation = $this->navigation($this->actingAs($admin)->get(route('admin.settings'))->assertOk()->getContent());
        foreach (['School', 'Settings'] as $text) $this->assertStringContainsString($text, $navigation);
        foreach (['Dashboard', 'Accounts', 'Monitor'] as $text) $this->assertStringNotContainsString($text, $navigation);
    }
}
