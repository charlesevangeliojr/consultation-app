<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginPasswordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_login_pages_have_an_accessible_password_eye_button(): void
    {
        foreach (['portal.login', 'admin.login'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('js/password-visibility.js')
                ->assertSee('data-login-password-toggle', false)
                ->assertSee('type="password"', false)
                ->assertSee('autocomplete="current-password"', false)
                ->assertSee('aria-label="Show password"', false)
                ->assertSee('aria-pressed="false"', false)
                ->assertSee('aria-controls="password"', false);
        }
    }
}
