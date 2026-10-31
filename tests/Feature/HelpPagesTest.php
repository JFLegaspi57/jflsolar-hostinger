<?php

namespace Tests\Feature;

use App\Models\PageText as PageTextRow;
use App\Models\SiteSetting;
use App\Support\Faq;
use App\Support\PublicContent;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    public function test_faq_lists_every_question_with_live_details_and_structured_data(): void
    {
        $res = $this->get('/faq')->assertOk()
            ->assertSee('Frequently asked questions')
            ->assertSee('Which system do I need: on-grid, hybrid or off-grid?')
            ->assertSee('Read our net metering guide')
            ->assertSee('"@type":"FAQPage"', false)
            ->assertDontSee('{order}')
            ->assertDontSee('{phone}');
        $this->assertSame(Faq::COUNT, substr_count($res->getContent(), 'class="faq-item"'));
        $res->assertSee(SiteSetting::query()->first()->phone_display);
    }

    public function test_staff_can_reword_an_answer_in_page_text(): void
    {
        PageTextRow::query()->create(['key' => 'faq.q6.a', 'value' => 'Yes, we deliver within Cavite. Call {phone}.']);
        PublicContent::flush();
        $this->get('/faq')->assertOk()->assertSee('Yes, we deliver within Cavite. Call '.SiteSetting::query()->first()->phone_display.'.');
        $this->get('/admin/text/faq')->assertRedirect(); // staff only
        $staff = \App\Models\User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($staff)->get('/admin/text/faq')->assertOk()->assertSee('Answer 6')->assertSee('{order}');
    }

    public function test_net_metering_guide_and_links(): void
    {
        $this->get('/net-metering')->assertOk()
            ->assertSee('Net metering in the Philippines')
            ->assertSee('100 kW or smaller', false)
            ->assertSee('blended generation cost');
        $this->get('/services')->assertSee('href="/net-metering"', false)->assertSee('href="/faq"', false);
        $this->get('/')->assertSee('Net metering guide')->assertSee('>FAQ<', false);
        $this->get('/sitemap.xml')->assertSee('/faq')->assertSee('/net-metering');
    }

    public function test_contact_page_shows_a_map(): void
    {
        $this->get('/contact')->assertOk()->assertSee('google.com/maps?q=', false)->assertSee('Waze');
    }

    public function test_local_business_data_includes_parsed_opening_hours(): void
    {
        SiteSetting::query()->first()->update(['business_hours' => '8AM to 5PM Monday to Saturday']);
        PublicContent::flush();
        $this->get('/')->assertSee('"@type":"LocalBusiness"', false)
            ->assertSee('"opens":"08:00","closes":"17:00"', false);
        $this->assertNull(Seo::openingHours('By appointment'));
    }

    public function test_tracking_is_off_until_set_up_and_needs_the_live_site(): void
    {
        $this->get('/')->assertDontSee('id="track-consent"', false)->assertDontSee('google-site-verification', false);
        $this->get('/privacy')->assertSee('does not use Google Analytics');

        config(['site.ga4_id' => 'G-TEST1234', 'site.meta_pixel_id' => '1234567890']);
        $this->get('/')->assertDontSee('id="track-consent"', false); // preview site: never

        config(['site.env' => 'production', 'site.google_verification' => 'abc123']);
        $this->get('/')->assertSee('id="track-consent"', false)
            ->assertSee('"G-TEST1234"', false)
            ->assertSee('<meta name="google-site-verification" content="abc123">', false)
            ->assertDontSee('googletagmanager.com/gtag/js?id=G', false); // only added after "Allow"
        $this->get('/privacy')->assertSee('id="tracking"', false)->assertSee('Only if you choose “Allow”', false);
    }
}
