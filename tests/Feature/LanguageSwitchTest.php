<?php

namespace Tests\Feature;

use App\Support\Lang;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    public function test_header_has_both_flags_and_english_is_the_default(): void
    {
        $this->get('/')->assertOk()->assertSee('aria-label="Language"', false)
            ->assertSee('/lang/fil?back=%2F', false)->assertSee('/lang/en?back=%2F', false);
        $this->get('/faq')->assertOk()->assertSee('<html lang="en-PH">', false);
    }

    public function test_choosing_filipino_opens_filipino_pages_where_they_exist(): void
    {
        $this->get('/lang/fil?back='.rawurlencode('/faq'))->assertRedirect('/faq/tagalog')->assertCookie(Lang::COOKIE, 'fil');
        $this->get('/lang/fil?back=/products')->assertRedirect('/products');             // no Filipino version: stays English
        $this->get('/lang/fil?back=https://evil.example/x')->assertRedirect('/');        // only local addresses

        $this->withCookie(Lang::COOKIE, 'fil')->get('/net-metering')->assertRedirect('/net-metering/tagalog');
        $this->withCookie(Lang::COOKIE, 'fil')->get('/services')->assertOk();
        $this->withCookie(Lang::COOKIE, 'fil')->get('/faq/tagalog')->assertOk()->assertSee('<html lang="fil">', false);

        $this->get('/lang/en?back='.rawurlencode('/faq/tagalog'))->assertRedirect('/faq')->assertCookie(Lang::COOKIE, 'en');
        $this->withCookie(Lang::COOKIE, 'en')->get('/faq')->assertOk();
    }
}
