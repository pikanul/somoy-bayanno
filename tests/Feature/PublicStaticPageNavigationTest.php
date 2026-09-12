<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicStaticPageNavigationTest extends TestCase
{
    public function test_home_page_links_to_clickable_demo_pages(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee(route('static.show', 'national'), false)
            ->assertSee(route('static.show', 'videos'), false)
            ->assertSee(route('static.show', 'photos'), false)
            ->assertDontSee('href="#"', false);
    }

    public function test_demo_page_renders_for_navigation_clicks(): void
    {
        $response = $this->get(route('static.show', 'national'));

        $response
            ->assertOk()
            ->assertSee('জাতীয়')
            ->assertSee('সম্পর্কিত বিষয়');
    }
}
