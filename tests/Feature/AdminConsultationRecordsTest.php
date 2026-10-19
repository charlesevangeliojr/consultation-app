<?php

namespace Tests\Feature;

use App\Models\ConsultationAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConsultationRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_list_has_only_date_and_teacher_with_full_read_only_details_in_a_dialog(): void
    {
        $teacher = User::factory()->create(['name' => 'Fictional Teacher']);
        $student = User::factory()->create(['name' => 'Fictional Student']);
        $booking = ConsultationAppointment::create([
            'teacher_id' => $teacher->id, 'student_id' => $student->id,
            'starts_at' => now()->addDay()->startOfHour(), 'ends_at' => now()->addDay()->startOfHour()->addMinutes(30),
            'status' => 'rejected', 'is_proposed' => true, 'rejection_reason' => 'time_taken',
            'topic' => '<script>alert("private")</script>',
        ]);
        $response = $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.consultations', ['day' => $booking->starts_at->toDateString()]))->assertOk();
        preg_match('/<table>[\s\S]*?<\/table>/', $response->getContent(), $table);
        $this->assertSame(2, substr_count($table[0], '<th scope="col">'));
        $this->assertStringContainsString('Date &amp; time', $table[0]);
        $this->assertStringContainsString('Fictional Teacher', $table[0]);
        $this->assertStringNotContainsString('Fictional Student', $table[0]);
        $this->assertStringNotContainsString('private', $table[0]);
        $response->assertSee('id="booking-detail-'.$booking->id.'"', false)->assertSee('aria-haspopup="dialog"', false)
            ->assertSee('Fictional Student')->assertSee('Student-proposed time')->assertSee('Not specified')
            ->assertSee('another accepted consultation took this time')->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(', false)->assertSee('Read-only record. Teachers manage booking approval.');
    }

    public function test_empty_booking_list_uses_two_column_empty_state_and_retains_permissions(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.consultations'))->assertOk()
            ->assertSee('colspan="2"', false)->assertSee('No bookings yet.');
        $this->actingAs(User::factory()->create())->get(route('admin.consultations'))->assertForbidden();
    }
}
