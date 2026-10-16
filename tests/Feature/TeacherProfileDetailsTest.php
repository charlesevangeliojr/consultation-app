<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherProfileDetailsTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesCompleteTeacherProfiles;

    private function teacher(): User
    {
        $role = Role::firstOrCreate(['name' => 'Teacher'], ['is_super_admin' => false]);
        $teacher = User::factory()->create(['role_id' => $role->id]);
        $this->createCompleteTeacherProfile($teacher);
        return $teacher;
    }

    public function test_country_formats_are_validated_saved_and_restored_without_duplicate_calling_codes(): void
    {
        $teacher = $this->teacher();
        $this->actingAs($teacher);
        foreach (['PH' => ['9123456789', '+639123456789'], 'US' => ['2025550123', '+12025550123'], 'CA' => ['4165550123', '+14165550123'], 'GB' => ['7700900123', '+447700900123'], 'SG' => ['81234567', '+6581234567']] as $country => [$national, $international]) {
            $this->put(route('consultations.teacher-profile.update'), array_replace($this->teacherProfileDetails($teacher), ['phone_country' => $country, 'phone_number' => $national]))->assertSessionHasNoErrors();
            $profile = $teacher->teacher->fresh();
            $this->assertSame($international, $profile->contact_number);
            $this->assertSame($country, $profile->phone_country);
            $this->assertTrue($profile->hasCompleteProfile());
            $this->get(route('consultations.teacher-profile.edit'))
                ->assertViewHas('phone', ['country' => $country, 'number' => $national])
                ->assertSee('>PH +63</option>', false)
                ->assertDontSee('PH +63 · Philippines');
        }
    }

    public function test_invalid_phone_countries_and_national_formats_are_rejected_without_writes(): void
    {
        $teacher = $this->teacher();
        $this->actingAs($teacher);
        foreach ([['PH', '09123456789', 'phone_number'], ['PH', '639123456789', 'phone_number'], ['US', '123', 'phone_number'], ['ZZ', '9123456789', 'phone_country'], [['PH'], '9123456789', 'phone_country']] as [$country, $national, $error]) {
            $this->putJson(route('consultations.teacher-profile.update'), array_replace($this->teacherProfileDetails($teacher), ['phone_country' => $country, 'phone_number' => $national]))
                ->assertUnprocessable()->assertJsonValidationErrors($error);
        }
        $this->assertSame('+639123456789', $teacher->teacher->fresh()->contact_number);
    }

    public function test_sex_and_structured_address_are_saved_with_ncr_and_manual_entry_supported(): void
    {
        $teacher = $this->teacher();
        $payload = $this->teacherProfileDetails($teacher);
        $this->actingAs($teacher)->put(route('consultations.teacher-profile.update'), $payload)->assertSessionHasNoErrors();
        $profile = $teacher->teacher->fresh();
        $this->assertNull($profile->province);
        $this->assertSame('Female', $profile->gender);
        $this->assertSame($payload['region'], $profile->region);
        $this->assertStringContainsString('Quezon City', $profile->address);
        $this->get(route('consultations.teacher-profile.edit'))->assertSee('Sex')->assertSee('region-options')
            ->assertSee('address-suggestions.js')->assertSee('value="Quezon City"', false)
            ->assertDontSee('Assigned and verified by your administrator.');
        $this->put(route('consultations.teacher-profile.update'), array_replace($payload, ['gender' => 'invalid', 'city' => '', 'barangay' => '']))
            ->assertSessionHasErrors(['gender', 'city', 'barangay']);
        $this->assertSame($profile->address, $profile->fresh()->address);
        $this->put(route('consultations.teacher-profile.update'), array_replace($payload, ['gender' => 'Male', 'region' => 'Manually typed region', 'province' => 'Manually typed province', 'city' => 'Manual city', 'barangay' => 'Manual barangay']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Male', $profile->fresh()->gender);
        $this->assertStringContainsString('Manual barangay, Manual city, Manually typed province, Manually typed region', $profile->fresh()->address);
    }

    public function test_teachers_publish_offerings_without_admin_confirmation_and_preserve_subject_assignments(): void
    {
        $teacher = $this->teacher();
        $subject = Subject::create(['name' => 'New consultation subject']);
        $teacher->teacher->subjects()->sync([$subject->id]);
        $offering = 'Algebra coaching <script>alert("test")</script>';
        $payload = array_replace($this->teacherProfileDetails($teacher), ['subject_ids' => [999999], 'consultation_offerings' => $offering, 'consultation_approved' => true]);
        $this->actingAs($teacher)->put(route('consultations.teacher-profile.update'), $payload)->assertSessionHasNoErrors();
        $profile = $teacher->teacher->fresh();
        $this->assertSame([$subject->id], $profile->subjects->modelKeys());
        $this->assertSame($offering, $profile->consultation_offerings);
        $this->assertFalse($profile->consultation_approved);
        $this->put(route('consultations.teacher-profile.update'), array_replace($payload, ['subject_ids' => [999999], 'consultation_offerings' => '']))
            ->assertSessionHasErrors(['consultation_offerings']);
        $this->assertSame($offering, $profile->fresh()->consultation_offerings);
        // Existing consultation permission still determines which teachers students may book.
        $profile->update(['consultation_approved' => true]);
        $role = Role::firstOrCreate(['name' => 'Student'], ['is_super_admin' => false]);
        $student = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($student)->get(route('consultations.browse', ['teacher' => $teacher->id]))
            ->assertOk()->assertSee('New consultation subject')->assertSee('What I can help you with')
            ->assertSee($offering)->assertDontSee('<script>alert("test")</script>', false)
            ->assertDontSee($profile->address)->assertDontSee($profile->contact_number);
    }
}
