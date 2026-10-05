<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicMarketingHttpTest extends TestCase
{
    public function test_homepage_has_a_description_and_a_real_heading(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="description" content="Resisquare helps landlords manage homes, rent, and repairs in one workspace.">', false)
            ->assertSee('Revolutionize Your', false)
            ->assertDontSee('<h1 class="modal-title', false);
    }

    public function test_robots_allows_the_public_site_and_sitemap_lists_marketing_pages(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('User-agent: *', false)
            ->assertSee('Allow: /', false)
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false)
            ->assertDontSee('Disallow: /'."\n", false);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'), false)
            ->assertSee(route('pricing'), false)
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false);
    }
}
