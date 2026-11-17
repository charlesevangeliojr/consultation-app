<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccountRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_show_only_name_and_role_in_table_with_details_and_actions_in_modal(): void
    {
        $student = User::factory()->create(['name' => 'Demo Record Student', 'email' => 'record@example.com', 'role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        $student->student()->create(['lrn' => '123456789012', 'status' => 'active']);
        $response = $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.accounts'))->assertOk();
        preg_match('/<table>[\s\S]*?<\/table>/', $response->getContent(), $table);
        $this->assertSame(2, substr_count($table[0], '<th scope="col">'));
        $this->assertStringContainsString('Demo Record Student', $table[0]);
        $this->assertStringNotContainsString('record@example.com', $table[0]);
        $this->assertStringNotContainsString('123456789012', $table[0]);
        $response->assertSee('id="account-detail-'.$student->id.'"', false)->assertSee('record@example.com')
            ->assertSee('123456789012')->assertSee('Online status')->assertSee('Last seen')
            ->assertSee('Edit account')->assertSee('data-open-delete="delete-user-'.$student->id.'"', false);
    }

    public function test_view_only_manager_can_open_details_but_cannot_see_edit_or_delete_actions(): void
    {
        $student = User::factory()->create(['name' => 'Viewable Record']);
        $manager = User::factory()->create();
        $manager->syncPermissions(['accounts' => ['view' => true]]);
        $this->actingAs($manager)->get(route('admin.accounts', ['search' => $student->email]))->assertOk()
            ->assertSee('id="account-detail-'.$student->id.'"', false)->assertSee('Protected account')
            ->assertDontSee('Edit account')->assertDontSee('data-open-delete=', false);
    }

    public function test_grade_section_management_has_clear_desktop_and_mobile_names(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.school'))->assertOk()
            ->assertSee('Grades &amp; sections', false)->assertSee('Academic setup')->assertSee('Configuration')
            ->assertDontSee('Academic lists')->assertDontSee('School setup');
    }

    public function test_accounts_display_profile_pictures_and_pagination_controls(): void
    {
        $admin = User::factory()->superAdmin()->create(['name' => 'Admin User']);
        $studentRole = Role::firstOrCreate(['name' => 'Student']);
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher']);

        $student = User::factory()->create(['name' => 'Photo Student', 'role_id' => $studentRole->id]);
        $student->student()->create(['profile_photo_path' => 'student-profiles/avatar.png']);

        $teacher = User::factory()->create(['name' => 'Photo Teacher', 'role_id' => $teacherRole->id]);
        $teacher->teacher()->create(['profile_photo_path' => 'teacher-profiles/avatar.png']);

        $response = $this->actingAs($admin)->get(route('admin.accounts'))->assertOk();
        $response->assertSee('student-profiles/avatar.png')
            ->assertSee('teacher-profiles/avatar.png')
            ->assertSee('Photo Student profile picture')
            ->assertSee('Photo Teacher profile picture')
            ->assertSee('Showing 1–3 of 3 · Page 1 of 1')
            ->assertSee('Per page');

        User::factory()->count(9)->create(['role_id' => $studentRole->id]);
        $this->actingAs($admin)->get(route('admin.accounts', ['per_page' => 10]))->assertOk()
            ->assertSee('Showing 1–10 of 12 · Page 1 of 2');
        $this->actingAs($admin)->get(route('admin.accounts', ['per_page' => 10, 'page' => 2]))->assertOk()
            ->assertSee('Showing 11–12 of 12 · Page 2 of 2');
    }
}
