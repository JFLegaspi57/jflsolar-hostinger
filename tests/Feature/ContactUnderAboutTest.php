<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactUnderAboutTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_is_in_the_about_drop_down_not_a_separate_menu_item(): void
    {
        $this->seed();
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('aria-label="About JFL Solar"', $html);
        $about = substr($html, strpos($html, 'aria-label="About JFL Solar"'), 600);
        $this->assertStringContainsString('href="/about"', $about);
        $this->assertStringContainsString('href="/contact"', $about);
        // Contact still in the footer; About is highlighted on the contact page.
        $this->assertStringContainsString('href="/contact"', substr($html, strpos($html, '<footer')));
        $contact = $this->get('/contact')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<a href="/about"\s+aria-current="page"#', $contact);
    }

    public function test_products_drop_down_starts_with_all_products(): void
    {
        $this->seed();
        $html = $this->get('/')->assertOk()->getContent();
        $menu = substr($html, strpos($html, 'aria-label="Product categories"'), 400);
        $this->assertMatchesRegularExpression('#<a href="/products"\s+class="nav-dd-link font-semibold">All Products</a>#', $menu);
        $this->assertStringContainsString('>All Products</a>', substr($html, strrpos($html, 'aria-label="Product categories"'), 700));
    }
}
