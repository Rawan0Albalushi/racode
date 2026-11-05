<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_arabic_by_default(): void
    {
        $this->get('/')->assertRedirect('/ar');
    }

    public function test_language_cookie_chooses_the_redirect(): void
    {
        $this->withCookie('locale', 'en')->get('/')->assertRedirect('/en');
    }

    public function test_home_is_arabic_and_english(): void
    {
        $this->get('/ar')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/Home')
            ->where('locale', 'ar')
            ->where('dir', 'rtl'));

        $this->get('/en')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('website/Home')
            ->where('locale', 'en')
            ->where('dir', 'ltr'));
    }

    public function test_missing_page_uses_the_error_screen(): void
    {
        $this->get('/ar/about')->assertNotFound()->assertInertia(fn (Assert $page) => $page
            ->component('website/Error')
            ->where('status', 404));
    }

    public function test_robots_and_security_headers(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        $this->get('/sitemap.xml')->assertOk()->assertSee('/ar')->assertSee('/en');
    }
}
