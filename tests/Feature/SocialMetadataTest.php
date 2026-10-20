<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Support\PwaIcons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_uses_settings_name_and_logo_in_public_sharing_metadata(): void
    {
        $settings = SiteSetting::current();
        $settings->update(['name' => 'School & Learning', 'logo_path' => 'logos/school.png']);
        $response = $this->get('/?i=1')->assertOk()
            ->assertSee('property="og:title" content="School &amp; Learning — School consultations"', false)
            ->assertSee('property="og:site_name" content="School &amp; Learning"', false)
            ->assertSee('property="og:image" content="'.url(PwaIcons::url(512, $settings->logo_path)).'"', false)
            ->assertSee('name="twitter:image"', false)->assertSee('property="og:image:alt"', false)
            ->assertSee('rel="canonical" href="'.url('/').'"', false)
            ->assertSee('property="og:url" content="'.url('/').'"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'name="description"'));
        $oldImage = PwaIcons::url(512, $settings->logo_path);
        $settings->update(['logo_path' => 'logos/replacement.png']);
        $this->get('/')->assertOk()->assertDontSee('property="og:image" content="'.url($oldImage).'"', false)
            ->assertSee('property="og:image" content="'.url(PwaIcons::url(512, $settings->logo_path)).'"', false);
    }

    public function test_missing_logo_uses_public_default_image_and_metadata_does_not_expose_private_pages(): void
    {
        $this->get('/')->assertOk()->assertSee('property="og:image" content="'.url('/pwa/icon-512.png').'"', false)
            ->assertSee('name="twitter:card" content="summary"', false);
        $this->get('/portal/login')->assertOk()->assertDontSee('property="og:image"', false);
    }

    public function test_all_meta_descriptions_are_exactly_155_characters(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        foreach (['name="description"', 'property="og:description"', 'name="twitter:description"'] as $attribute) {
            preg_match('/'.preg_quote($attribute, '/').' content="([^"]*)"/', $html, $matches);
            $this->assertSame(155, mb_strlen(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }
    }
}
