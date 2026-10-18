<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_navigation_groups_and_current_links_match_on_desktop_and_mobile(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        foreach (['admin.dashboard', 'admin.consultations', 'admin.accounts', 'admin.roles.index', 'admin.school', 'admin.settings'] as $route) {
            $response = $this->get(route($route))->assertOk()
                ->assertSee('admin-navigation.css')->assertSee('admin-navigation.js')
                ->assertSee('aria-controls="admin-mobile-menu"', false);
            $xpath = $this->xpath($response->getContent());
            foreach (['Admin navigation', 'Admin menu navigation'] as $label) {
                $navigation = '//nav[@aria-label="'.$label.'"]';
                $headings = $xpath->query($navigation.'//p');
                $this->assertSame(['Overview', 'People & access', 'School setup'], array_map(fn ($node) => trim($node->textContent), iterator_to_array($headings)));
                $active = $xpath->query($navigation.'//a[@aria-current="page"]');
                $this->assertCount(1, $active);
                $this->assertSame(route($route), $active->item(0)->getAttribute('href'));
            }
            $this->assertCount(5, $xpath->query('//nav[@aria-label="Admin mobile navigation"]//a'));
            $this->assertCount(1, $xpath->query('//nav[@aria-label="Admin mobile navigation"]//a[@aria-current="page"]'));
        }
    }

    public function test_limited_admin_navigation_hides_unavailable_groups_and_has_a_valid_home_link(): void
    {
        $user = User::factory()->create();
        $user->syncPermissions(['accounts' => ['view' => true]]);
        $html = $this->actingAs($user)->get(route('admin.roles.index'))->assertOk()->getContent();
        $xpath = $this->xpath($html);
        foreach (['Admin navigation', 'Admin menu navigation'] as $label) {
            $links = $xpath->query('//nav[@aria-label="'.$label.'"]//a');
            $this->assertSame([route('admin.accounts'), route('admin.roles.index')], array_map(fn ($node) => $node->getAttribute('href'), iterator_to_array($links)));
        }
        $this->assertSame(route('admin.accounts'), $xpath->query('//aside//a[contains(@class,"admin-brand")]')->item(0)->getAttribute('href'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Admin mobile navigation"]//a'));
        $this->get(route('admin.profile.edit'))->assertOk()->assertSee('My profile');
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }
}
