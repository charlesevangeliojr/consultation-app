<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_sections_separate_school_form_from_personal_push_controls(): void
    {
        $response = $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.settings'))->assertOk()
            ->assertSee('School identity')->assertSee('Queue privacy')->assertSee('Device notifications')
            ->assertSee('Save school settings')->assertSee('Send test notification');
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $this->assertCount(3, $xpath->query('//nav[@aria-label="Settings sections"]/a'));
        $this->assertCount(1, $xpath->query('//form[@aria-label="School settings form"]'));
        $this->assertCount(0, $xpath->query('//form[@aria-label="School settings form"]//*[@data-push-settings]'));
        $this->assertCount(1, $xpath->query('//section[@id="notification-settings"]//*[@data-push-test]'));
        $this->assertCount(0, $xpath->query('//form//form'));
    }

    public function test_read_only_admin_can_manage_own_device_but_not_school_settings(): void
    {
        $viewer = User::factory()->create();
        $viewer->syncPermissions(['settings' => ['view' => true]]);
        $this->actingAs($viewer)->get(route('admin.settings'))->assertOk()
            ->assertSee('School settings are read-only')->assertDontSee('Save school settings')
            ->assertSee('fieldset disabled', false)->assertSee('data-push-enable', false);
        $this->put(route('admin.settings.update'), ['name' => 'Unauthorized change'])->assertForbidden();
    }
}
