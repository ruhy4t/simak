<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationSmokeTest extends TestCase
{
    public function test_home_redirects_guests_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('Server-Timing')
            ->assertViewIs('auth.login')
            ->assertSee(route('public.statistics'), false)
            ->assertSee('Lihat Statistik Publik')
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('name="remember_credentials"', false)
            ->assertDontSee('name="remember"', false)
            ->assertDontSee('Jika dicentang, username diingat di perangkat ini.');
    }

    public function test_authenticated_routes_redirect_guests_to_login(): void
    {
        $this->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_ajax_debug_requires_authentication(): void
    {
        $this->get('/ajax/debug-connection')
            ->assertRedirect(route('login'));
    }
}
