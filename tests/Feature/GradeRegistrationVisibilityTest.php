<?php

namespace Tests\Feature;

use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeRegistrationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_grades_seven_to_twelve_are_provided_and_seeding_preserves_choices(): void
    {
        $this->assertEqualsCanonicalizing(GradeLevel::STANDARD_NAMES, GradeLevel::pluck('name')->all());
        $grade = GradeLevel::where('name', 'Grade 7')->firstOrFail();
        $grade->update(['registration_enabled' => false]);
        $this->seed(\Database\Seeders\GradeLevelSeeder::class);
        $this->assertDatabaseCount('grade_levels', 6);
        $this->assertFalse($grade->fresh()->registration_enabled);
    }

    public function test_admin_checkboxes_control_public_options_and_hidden_grades_are_rejected(): void
    {
        $grade = GradeLevel::where('name', 'Grade 7')->firstOrFail();
        $hidden = GradeLevel::where('name', 'Grade 8')->firstOrFail();
        $section = Section::create(['name' => 'Hidden section', 'grade_level_id' => $hidden->id]);
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.school'))
            ->assertOk()->assertSee('name="grades[]"', false)->assertDontSee('New grade level');
        $this->put(route('admin.school.registration-grades'), ['grades' => [$grade->id]])->assertSessionHasNoErrors();
        $this->assertSame([$grade->id], GradeLevel::forRegistration()->pluck('id')->all());
        $this->assertDatabaseHas('sections', ['id' => $section->id]);
        auth()->logout();
        $this->get(route('register', ['role' => 'student']))->assertOk()->assertSee('Grade 7')
            ->assertDontSee('Grade 8')->assertDontSee('Hidden section');
        $this->post(route('register.store'), ['role' => 'student', 'grade_level_id' => $hidden->id, 'section_id' => $section->id])
            ->assertSessionHasErrors('grade_level_id');
    }

    public function test_all_grades_can_be_hidden_and_only_authorized_admins_can_change_them(): void
    {
        $viewer = User::factory()->create();
        $viewer->syncPermissions(['settings' => ['view' => true]]);
        $this->actingAs($viewer)->get(route('admin.school'))->assertOk()->assertDontSee('Save registration grades');
        $this->put(route('admin.school.registration-grades'), [])->assertForbidden();
        $this->actingAs(User::factory()->superAdmin()->create())->put(route('admin.school.registration-grades'), [])->assertSessionHasNoErrors();
        $this->assertSame(0, GradeLevel::forRegistration()->count());
        $this->assertDatabaseCount('grade_levels', 6);
        $this->put(route('admin.school.registration-grades'), ['grades' => [99999]])->assertSessionHasErrors('grades.0');
        $grade = GradeLevel::firstOrFail();
        $this->put(route('admin.school.update', ['grade-levels', $grade->id]), ['name' => 'Changed'])->assertNotFound();
    }
}
