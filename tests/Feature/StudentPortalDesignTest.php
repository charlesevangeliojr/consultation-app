<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentPortalDesignTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        $role = Role::firstOrCreate(['name' => 'Student'], ['is_super_admin' => false]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $user->student()->create(['status' => 'active', 'gender' => 'Female']);
        return $user;
    }

    public function test_student_pages_share_full_width_shell_avatar_account_menu_and_active_navigation(): void
    {
        $user = $this->student();
        foreach (['consultations.dashboard', 'consultations.browse', 'consultations.student-consultations.index', 'consultations.student-profile.edit'] as $route) {
            $response = $this->actingAs($user)->get(route($route))->assertOk()
                ->assertSee('student-portal.css')->assertSee('Student workspace')
                ->assertSee('Student sidebar')->assertSee('data-student-account-menu', false)
                ->assertSee('Student account navigation')->assertSee('data-mobile-account-menu', false)
                ->assertSee('id="student-workspace"', false)->assertSee($user->name)->assertSee($user->email);
            $this->assertSame(1, preg_match('/<nav[^>]*aria-label="Student mobile navigation"[^>]*>(.*?)<\/nav>/s', $response->getContent(), $matches));
            $this->assertSame(1, substr_count($matches[1], 'aria-current="page"'));
            $this->assertStringContainsString(route($route), $matches[1]);
        }
    }

    public function test_student_profile_uses_teacher_style_cards_and_preserves_existing_profile_fields(): void
    {
        Storage::fake('public');
        $user = $this->student();
        $this->actingAs($user)->get(route('consultations.student-profile.edit'))->assertOk()
            ->assertSee('data-student-profile-form', false)->assertSee('School information')
            ->assertSee('Admin managed')->assertSee('Personal information')->assertSee('Sex')
            ->assertSee('student-profile.js')->assertSee('data-photo-crop-dialog', false);

        $name = $user->name;
        $this->put(route('consultations.student-profile.update'), [
            'email' => 'student.updated@example.com', 'name' => 'Must not overwrite admin-managed name',
            'contact_number' => '+639123456789', 'birthday' => '2008-05-12', 'gender' => 'Female',
            'lrn' => '123456789012', 'address' => '12 Fictional Street, Quezon City', 'status' => 'active',
            'profile_photo' => UploadedFile::fake()->image('cropped-photo.jpg', 512, 512),
        ])->assertSessionHasNoErrors()->assertRedirect(route('consultations.student-profile.edit'));
        $user->refresh();
        $this->assertSame($name, $user->name);
        $this->assertSame('student.updated@example.com', $user->email);
        $this->assertSame('123456789012', $user->student->lrn);
        Storage::disk('public')->assertExists($user->student->profile_photo_path);
        $this->get(route('consultations.student-profile.edit'))->assertOk()
            ->assertSee('/storage/'.$user->student->profile_photo_path, false)
            ->assertDontSee('Saved address');
    }

    public function test_dashboard_uses_compact_mobile_summary_labels_without_changing_desktop_labels(): void
    {
        $this->actingAs($this->student())->get(route('consultations.dashboard'))->assertOk()
            ->assertSee('aria-label="Your consultation summary"', false)
            ->assertSee('<dl class="student-stat-grid"', false)
            ->assertSee('Days')->assertSee('Upcoming')->assertSee('History')
            ->assertSee('Scheduled days')->assertSee('Next consultations')->assertSee('Booking history &amp; updates', false)
            ->assertSee('data-stat-tone="sky"', false)->assertSee('data-stat-tone="green"', false)->assertSee('data-stat-tone="amber"', false);
    }

    public function test_invalid_student_profile_preserves_saved_details_and_displays_errors_in_new_form(): void
    {
        $user = $this->student();
        $this->actingAs($user)->from(route('consultations.student-profile.edit'))
            ->put(route('consultations.student-profile.update'), ['email' => 'invalid', 'status' => 'invalid', 'lrn' => '12'])
            ->assertSessionHasErrors(['email', 'lrn']);
        $this->get(route('consultations.student-profile.edit'))->assertOk()
            ->assertSee('Please review your profile details')->assertSee('role="alert"', false);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame('active', $user->student->fresh()->status);
    }

    public function test_non_students_cannot_open_or_submit_the_student_profile(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->get(route('consultations.student-profile.edit'))->assertForbidden();
        $this->put(route('consultations.student-profile.update'), [])->assertForbidden();
    }

    public function test_student_profile_does_not_require_or_allow_changing_account_status(): void
    {
        $user = $this->student();
        $this->assertSame('active', $user->student->status);
        $this->actingAs($user)->get(route('consultations.student-profile.edit'))->assertOk()
            ->assertDontSee('name="status"', false)->assertDontSee('Account status');

        $this->put(route('consultations.student-profile.update'), ['contact_number' => '+639171234567'])
            ->assertSessionHasNoErrors();
        $this->assertSame('active', $user->student->fresh()->status);

        $this->put(route('consultations.student-profile.update'), ['status' => 'inactive'])
            ->assertSessionHasNoErrors();
        $this->assertSame('active', $user->student->fresh()->status);

        $user->student->update(['status' => 'inactive']);
        $this->put(route('consultations.student-profile.update'), ['status' => 'active'])
            ->assertSessionHasNoErrors();
        $this->assertSame('inactive', $user->student->fresh()->status);
    }

    public function test_student_structured_address_supports_ncr_manual_entry_and_restores_fields(): void
    {
        $user = $this->student();
        $details = ['email' => $user->email, 'status' => 'active', 'house_number' => '12 Fictional Street', 'region' => 'National Capital Region (NCR)', 'province' => null, 'city' => 'Quezon City', 'barangay' => 'Diliman'];
        $this->actingAs($user)->put(route('consultations.student-profile.update'), $details)->assertSessionHasNoErrors();
        $this->assertSame('12 Fictional Street, Diliman, Quezon City, National Capital Region (NCR)', $user->student->fresh()->address);
        $this->get(route('consultations.student-profile.edit'))->assertOk()->assertSee('data-address-suggestions', false)
            ->assertSee('address-suggestions.js')->assertSee('value="Quezon City"', false)->assertSee('value="Diliman"', false)
            ->assertDontSee('Saved address')
            ->assertDontSee('name="postal_code"', false);
        // The server does not depend on the external suggestions API.
        $details['region'] = 'Manually typed region';
        $details['city'] = 'Manually typed city';
        $details['barangay'] = 'Manually typed barangay';
        $this->put(route('consultations.student-profile.update'), $details)->assertSessionHasNoErrors();
        $this->assertSame('Manually typed city', $user->student->fresh()->city);
    }

    public function test_address_requires_region_city_and_barangay_when_started_and_rejects_partial_or_oversized_input(): void
    {
        $user = $this->student();
        $user->student->update(['address' => 'Original saved address']);
        $this->actingAs($user)->put(route('consultations.student-profile.update'), ['email' => $user->email, 'status' => 'active', 'region' => 'NCR'])
            ->assertSessionHasErrors(['city', 'barangay']);
        $this->assertSame('Original saved address', $user->student->fresh()->address);
        $this->put(route('consultations.student-profile.update'), ['email' => $user->email, 'status' => 'active', 'region' => str_repeat('x', 151), 'city' => 'City', 'barangay' => 'Barangay'])
            ->assertSessionHasErrors('region');
        $this->assertSame('Original saved address', $user->student->fresh()->address);
    }

    public function test_unmodified_legacy_address_is_preserved_when_saving_other_profile_details(): void
    {
        $user = $this->student();
        $user->student->update(['address' => 'Original saved address']);
        $this->actingAs($user)->put(route('consultations.student-profile.update'), ['email' => $user->email, 'status' => 'active', 'region' => '', 'city' => '', 'barangay' => '', 'province' => '', 'house_number' => ''])->assertSessionHasNoErrors();
        $this->assertSame('Original saved address', $user->student->fresh()->address);
        $this->get(route('consultations.student-profile.edit'))->assertOk()->assertDontSee('Saved address');
    }

    public function test_legacy_registration_address_prefills_individual_fields_and_can_be_saved(): void
    {
        $user = $this->student();
        $user->student->update(['address' => 'Davao Region, Marapangi, City of Davao, Davao Del Sur']);
        $this->actingAs($user)->get(route('consultations.student-profile.edit'))->assertOk()
            ->assertSee('value="Davao Region"', false)
            ->assertSee('value="Marapangi"', false)
            ->assertSee('value="City of Davao"', false)
            ->assertSee('value="Davao Del Sur"', false)
            ->assertDontSee('Saved address')->assertDontSee('This address is kept');

        $this->put(route('consultations.student-profile.update'), [
            'status' => 'active', 'region' => 'Davao Region', 'barangay' => 'Marapangi',
            'city' => 'City of Davao', 'province' => 'Davao Del Sur', 'house_number' => '',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('students', [
            'user_id' => $user->id, 'region' => 'Davao Region', 'barangay' => 'Marapangi',
            'city' => 'City of Davao', 'province' => 'Davao Del Sur',
        ]);
    }

    public function test_legacy_registration_address_restores_house_number_and_omits_postal_code(): void
    {
        $user = $this->student();
        $user->student->update(['address' => '12 Example Street, Davao Region, Marapangi, City of Davao, Davao Del Sur, 8000']);
        $this->actingAs($user)->get(route('consultations.student-profile.edit'))->assertOk()
            ->assertSee('value="12 Example Street"', false)->assertSee('value="Davao Region"', false)
            ->assertSee('value="Marapangi"', false)->assertSee('value="City of Davao"', false)
            ->assertSee('value="Davao Del Sur"', false)->assertDontSee('name="postal_code"', false);
    }
}
