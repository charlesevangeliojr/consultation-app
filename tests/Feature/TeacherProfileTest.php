<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherProfileTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function teacher(): User
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->createCompleteTeacherProfile($user, ['consultation_approved' => true]);
        return $user;
    }

    public function test_teacher_can_open_profile_with_active_desktop_and_mobile_links(): void
    {
        $user = $this->teacher();
        $subject = Subject::create(['name' => 'Profile Mathematics']);
        $user->teacher->subjects()->attach($subject);
        $response = $this->actingAs($user)->get(route('consultations.teacher-profile.edit'))
            ->assertOk()->assertSee('Your profile')->assertDontSee('Consultation subjects')
            ->assertSee('profile picture')->assertSee('teacher-calendar.css')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('data-photo-crop-dialog', false)->assertSee('Crop profile picture')
            ->assertSee('profile-photo-cropper.js');
        foreach (['Teacher account navigation', 'Teacher mobile navigation'] as $label) {
            $this->assertSame(1, preg_match('/<nav\b[^>]*aria-label="'.$label.'"[^>]*>(.*?)<\/nav>/s', $response->getContent(), $matches));
            $this->assertMatchesRegularExpression('/<a[^>]*href="'.preg_quote(route('consultations.teacher-profile.edit'), '/').'"[^>]*aria-current="page"/', $matches[1]);
            $this->assertStringNotContainsString('aria-current="location"', $matches[1]);
        }
        $this->assertSame(1, preg_match('/<nav\b[^>]*aria-label="Teacher desktop navigation"[^>]*>(.*?)<\/nav>/s', $response->getContent(), $matches));
        $this->assertStringNotContainsString('My profile', $matches[1]);
        $this->assertStringNotContainsString('aria-current="page"', $matches[1]);
        $response->assertSee('data-teacher-account-menu', false)->assertSee('Account options')
            ->assertSee('Sign out')->assertSee('teacher-account-menu.js');
    }

    public function test_checklist_is_hidden_for_complete_profiles_and_visible_for_incomplete_profiles(): void
    {
        $user = $this->teacher();
        $response = $this->actingAs($user)->get(route('consultations.teacher-profile.edit'))->assertOk();
        $this->assertMatchesRegularExpression('/<section[^>]*class="profile-card profile-progress-card"[^>]*\bhidden\b[^>]*>/', $response->getContent());

        $user->teacher->update(['consultation_offerings' => null]);
        $response = $this->get(route('consultations.teacher-profile.edit'))->assertOk();
        $this->assertDoesNotMatchRegularExpression('/<section[^>]*class="profile-card profile-progress-card"[^>]*\bhidden\b[^>]*>/', $response->getContent());
        $response->assertSee('Profile checklist');
    }

    public function test_teacher_can_edit_own_details_but_not_school_permissions_or_another_account(): void
    {
        $user = $this->teacher();
        $other = $this->teacher();
        $subject = Subject::create(['name' => 'Assigned subject']);
        $user->teacher->subjects()->attach($subject);
        $originalSubjects = $user->teacher->subjects()->orderBy('subjects.id')->pluck('subjects.id')->all();
        $this->actingAs($user)->put(route('consultations.teacher-profile.update'), [
            'name' => 'Updated Teacher', 'email' => 'updated.teacher@example.com',
            'phone_country' => 'PH', 'phone_number' => '9123456789', 'birthday' => '1990-05-10',
            'gender' => 'Female', 'subject_ids' => [$subject->id],
            'user_id' => $other->id, 'role_id' => $other->role_id,
            'consultation_approved' => false, 'subjects' => [], 'slot_minutes' => 5,
        ] + $this->teacherProfileDetails($user))->assertRedirect(route('consultations.teacher-profile.edit'))->assertSessionHas('status');
        $this->assertSame('Updated Teacher', $user->fresh()->name);
        $this->assertSame('updated.teacher@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        $profile = $user->teacher->fresh();
        $this->assertSame('+639123456789', $profile->contact_number);
        $this->assertSame('1990-05-10', $profile->birthday->toDateString());
        $this->assertSame('Female', $profile->gender);
        $this->assertSame('12 Demo Street, Demo barangay, Quezon City, National Capital Region (NCR), 1100', $profile->address);
        $this->assertTrue($profile->consultation_approved);
        $this->assertSame($originalSubjects, $profile->subjects()->orderBy('subjects.id')->pluck('subjects.id')->all());
        $this->assertSame($other->name, $other->fresh()->name);
    }

    public function test_photo_upload_replacement_and_detail_only_edits_preserve_the_correct_file(): void
    {
        Storage::fake('public');
        $user = $this->teacher();
        $details = $this->teacherProfileDetails($user);
        $this->actingAs($user)->put(route('consultations.teacher-profile.update'), $details + [
            'profile_photo' => UploadedFile::fake()->image('portrait.png'),
        ])->assertSessionHasNoErrors();
        $first = $user->teacher->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);
        $this->get(route('consultations.teacher-profile.edit'))->assertSee(Storage::disk('public')->url($first));
        $this->put(route('consultations.teacher-profile.update'), $details)->assertSessionHasNoErrors();
        $this->assertSame($first, $user->teacher->fresh()->profile_photo_path);
        Storage::disk('public')->assertExists($first);
        $this->put(route('consultations.teacher-profile.update'), $details + [
            'profile_photo' => UploadedFile::fake()->image('replacement.jpg'),
        ])->assertSessionHasNoErrors();
        $second = $user->teacher->fresh()->profile_photo_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertExists($second);
        Storage::disk('public')->assertMissing($first);
    }

    public function test_invalid_details_and_photos_do_not_update_the_account(): void
    {
        Storage::fake('public');
        $user = $this->teacher();
        $other = $this->teacher();
        $this->actingAs($user)->put(route('consultations.teacher-profile.update'), [
            'name' => '', 'email' => $other->email, 'birthday' => today()->toDateString(),
            'phone_country' => 'PH', 'phone_number' => str_repeat('1', 31), 'region' => str_repeat('a', 151),
            'profile_photo' => UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml'),
        ])->assertSessionHasErrors(['name', 'email', 'birthday', 'phone_number', 'region', 'profile_photo']);
        $this->put(route('consultations.teacher-profile.update'), [
            'name' => $user->name, 'email' => $user->email,
            'profile_photo' => UploadedFile::fake()->image('large.jpg')->size(2049),
        ])->assertSessionHasErrors('profile_photo');
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame('teacher-profiles/test-avatar.png', $user->teacher->fresh()->profile_photo_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_saved_photo_uses_same_origin_when_app_url_omits_the_development_port(): void
    {
        Storage::fake('public');
        config(['app.url' => 'http://localhost']);
        $user = $this->teacher();
        $path = UploadedFile::fake()->image('same-origin.png')->store('teacher-profiles', 'public');
        $user->teacher->update(['profile_photo_path' => $path]);

        $this->actingAs($user)->get('http://127.0.0.1:8000/consultations/teacher/profile')
            ->assertOk()->assertSee('src="/storage/'.$path.'"', false)
            ->assertDontSee('http://localhost/storage/', false);
        $this->assertSame('/storage/'.$path, Storage::disk('public')->url($path));
        Storage::disk('public')->assertExists($path);
    }

    public function test_guests_and_non_teachers_cannot_edit_teacher_profiles(): void
    {
        $this->get(route('consultations.teacher-profile.edit'))->assertRedirect(route('portal.login'));
        $this->put(route('consultations.teacher-profile.update'), [])->assertRedirect(route('portal.login'));
        foreach (['Student', 'Administrator'] as $name) {
            $role = Role::firstOrCreate(['name' => $name], ['is_super_admin' => false]);
            $this->actingAs(User::factory()->create(['role_id' => $role->id]));
            $this->get(route('consultations.teacher-profile.edit'))->assertForbidden();
            $this->put(route('consultations.teacher-profile.update'), [])->assertForbidden();
        }
    }
}
