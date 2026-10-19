<?php

namespace Tests\Feature;

use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccountSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_by_name_email_lrn_and_section(): void
    {
        $admin = User::factory()->superAdmin()->create(['name' => 'System Manager', 'email' => 'rootadmin@example.net']);
        $student = User::factory()->create(['name' => 'Alex Fictional', 'email' => 'alex@example.com', 'role_id' => Role::firstOrCreate(['name' => 'Student'])->id]);
        $grade = GradeLevel::where('name', 'Grade 10')->firstOrFail();
        $section = Section::create(['name' => 'Orchid', 'grade_level_id' => $grade->id]);
        $student->student()->create(['lrn' => '123456789012', 'section_id' => $section->id, 'grade_level_id' => $grade->id]);
        $other = User::factory()->create(['name' => 'Unrelated Person', 'email' => 'unrelated@example.net']);
        foreach (['aLeX', 'alex@example.com', '567890', 'ORCHID', '  Orchid  '] as $search) {
            $this->actingAs($admin)->get(route('admin.accounts', ['search' => $search]))->assertOk()
                ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->is($student))
                ->assertSee('Section: Orchid')->assertDontSee($other->name);
        }
        $this->get(route('admin.accounts', ['search' => 'not-a-match']))->assertOk()->assertSee('No users found.')->assertSee('0 users matching your search');
        $this->get(route('admin.accounts', ['search' => '   ']))->assertOk()->assertViewHas('users', fn ($users) => $users->total() === 3);
    }

    public function test_search_preserves_super_admin_visibility_and_permissions(): void
    {
        $root = User::factory()->superAdmin()->create(['name' => 'Protected Root']);
        $admin = User::factory()->create();
        $admin->syncPermissions(['accounts' => ['view' => true]]);
        $this->actingAs($admin)->get(route('admin.accounts', ['search' => $root->email]))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 0);
        $this->get(route('admin.accounts', ['search' => 'Root']))->assertOk()->assertViewHas('users', fn ($users) => $users->total() === 0);
        $this->actingAs(User::factory()->create())->get(route('admin.accounts', ['search' => 'Root']))->assertForbidden();
    }

    public function test_search_applies_before_pagination_and_keeps_search_in_page_links(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->count(17)->create(['name' => 'Matching User']);
        User::factory()->count(16)->create(['name' => 'Different User']);
        $this->actingAs($admin)->get(route('admin.accounts', ['search' => 'Matching']))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 17 && $users->count() === 15)
            ->assertSee('search=Matching', false);
        $this->get(route('admin.accounts', ['search' => 'Matching', 'page' => 2]))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 17 && $users->count() === 2);
    }

    public function test_search_validates_input_and_treats_wildcards_literally(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        User::factory()->create(['name' => 'Literal %_! Name']);
        User::factory()->create(['name' => 'Other Name']);
        $this->get(route('admin.accounts', ['search' => '%_!']))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 1);
        $this->get(route('admin.accounts', ['search' => str_repeat('a', 151)]))->assertSessionHasErrors('search');
        $this->get(route('admin.accounts', ['search' => ['bad']]))->assertSessionHasErrors('search');
    }
}
