<?php

namespace Tests\Unit;

use App\Admin\Saver;
use App\Support\Inquiries;
use App\Support\MediaStore;
use App\Support\PageText;
use App\Support\PageTextData;
use App\Support\Pricing;
use Tests\TestCase;

class RulesTest extends TestCase
{
    public function test_promotion_state_uses_inclusive_manila_days(): void
    {
        $this->assertSame('none', Pricing::promoState(null, null, '2026-10-15'));
        $this->assertSame('upcoming', Pricing::promoState('2026-10-16', '2026-10-31', '2026-10-15'));
        $this->assertSame('active', Pricing::promoState('2026-10-01', '2026-10-31', '2026-10-31'));
        $this->assertSame('ended', Pricing::promoState('2026-10-01', '2026-10-31', '2026-11-01'));
        // 23:30 UTC on Oct 31 is already Nov 1 in Manila.
        $this->assertSame('2026-11-01', Pricing::manilaToday(\Carbon\CarbonImmutable::parse('2026-10-31T23:30:00Z')));
    }

    public function test_price_display_modes_samples_and_withdrawn_promotions(): void
    {
        $this->assertSame('₱205,000', Pricing::display('fixed', 205000, 'Pickup', true)['headline']);
        $this->assertTrue(Pricing::display('fixed', 205000, null, true)['isSample']);
        $this->assertSame('From ₱550,000', Pricing::display('starting', 550000, null, false)['headline']);
        $this->assertSame('Price to be confirmed', Pricing::display('quote', null, null, false)['headline']);
        $ended = Pricing::display('fixed', 1000, 'basis', true, 'ended');
        $this->assertFalse($ended['hasAmount']);
        $this->assertSame('Promotion ended — request current price', $ended['headline']);
        $this->assertSame('₱1,234.50', Pricing::formatPhp(1234.5));
        $this->assertSame('6.2 kWp', Pricing::formatCapacity(6.2, 'kWp'));
        $this->assertSame('16.5 kWh', Pricing::formatCapacity('16.50', 'kWh'));
        $this->assertNull(Pricing::formatCapacity(null, 'kW'));
        $this->assertSame('Oct 1, 2026 – Oct 31, 2026', Pricing::promoWindow('2026-10-01', '2026-10-31'));
    }

    public function test_inquiry_validation_requires_one_usable_contact_method(): void
    {
        $base = Inquiries::read(['name' => 'Ana Cruz', 'inquiry_type' => 'residential', 'message' => 'Please quote a 6 kW system.']);
        $this->assertArrayHasKey('contact', Inquiries::validate($base));
        $this->assertSame([], Inquiries::validate(['phone' => '0917 123 4567'] + $base));
        $this->assertArrayHasKey('phone', Inquiries::validate(['phone' => '12'] + $base));
        $this->assertArrayHasKey('email', Inquiries::validate(['email' => 'not-an-email'] + $base));
        $this->assertArrayHasKey('inquiry_type', Inquiries::validate(['phone' => '0917 123 4567', 'inquiry_type' => 'bogus'] + $base));
        $this->assertArrayHasKey('item', Inquiries::validate(['phone' => '0917 123 4567', 'item_slug' => 'x', 'item_type' => ''] + $base));
    }

    public function test_inquiry_input_is_trimmed_bounded_and_stripped_of_control_characters(): void
    {
        $v = Inquiries::read(['name' => "  Ana\x07  Cruz ", 'message' => "Line one\r\n\r\nLine\ttwo", 'email' => 'ANA@Example.COM', 'item_type' => 'evil']);
        $this->assertSame('Ana Cruz', $v['name']);
        $this->assertSame("Line one\n\nLine two", $v['message']);
        $this->assertSame('ana@example.com', $v['email']);
        $this->assertSame('', $v['item_type']);
        $this->assertSame(100, mb_strlen(Inquiries::read(['name' => str_repeat('a', 500)])['name']));
    }

    public function test_form_token_enforces_minimum_fill_time_and_expiry(): void
    {
        $this->assertSame('too_fast', Inquiries::checkToken(Inquiries::issueToken()));
        $this->assertSame('ok', Inquiries::checkToken(Inquiries::issueToken(time() - 10)));
        $this->assertSame('expired', Inquiries::checkToken(Inquiries::issueToken(time() - 13 * 3600)));
        $this->assertSame('invalid', Inquiries::checkToken('forged'));
        $this->assertSame('invalid', Inquiries::checkToken(null));
    }

    public function test_media_paths_are_strictly_validated(): void
    {
        $id = '00000000-0000-4000-8000-000000000201';
        $this->assertNotNull(MediaStore::parse("packages/{$id}/{$id}/640.webp"));
        $this->assertNotNull(MediaStore::parse("products/{$id}/{$id}/datasheet.pdf"));
        $this->assertNull(MediaStore::parse("packages/{$id}/{$id}/../../.env"));
        // Any 1–4 digit width is a valid shape (small originals keep their own width);
        // MediaController additionally requires the file to be one the asset lists.
        $this->assertNotNull(MediaStore::parse("packages/{$id}/{$id}/400.webp"));
        $this->assertNull(MediaStore::parse("packages/{$id}/{$id}/12345.webp"));
        $this->assertNull(MediaStore::parse("packages/{$id}/{$id}/0640.webp"));
        $this->assertNull(MediaStore::parse("secrets/{$id}/{$id}/640.webp"));
        $this->assertNull(MediaStore::parse("packages/{$id}/{$id}/640.webp/extra"));
    }

    public function test_back_links_only_return_to_admin_lists(): void
    {
        $this->assertTrue(Saver::safeBack('/admin/products?q=goodwe&category=abc'));
        $this->assertFalse(Saver::safeBack('https://evil.example'));
        $this->assertFalse(Saver::safeBack('//evil.example/admin/products'));
        $this->assertFalse(Saver::safeBack('/admin/products?q=<script>'));
        $this->assertFalse(Saver::safeBack(null));
    }

    public function test_page_text_cleaning_and_registry_match(): void
    {
        $line = ['key' => 'home.hero.title', 'label' => 'Headline', 'kind' => 'line'];
        $list = ['key' => 'home.path1.points', 'label' => 'Points', 'kind' => 'list'];
        $this->assertSame(['value' => 'Two lines'], PageText::clean($line, "  Two\r\nlines  "));
        $this->assertSame(['value' => "a\nb"], PageText::clean($list, "a\r\n\r\n- b\n"));
        $this->assertArrayHasKey('error', PageText::clean($line, str_repeat('x', 161)));
        $this->assertArrayHasKey('error', PageText::clean($list, implode("\n", range(1, 9))));
        $this->assertSame(['One', 'Two', 'three'], PageText::lines("- One\n\n• Two \n  three"));

        $keys = [];
        foreach (array_keys(PageTextData::PAGES) as $slug) {
            $keys = array_merge($keys, PageText::keys(PageText::page($slug)));
        }
        $this->assertSame(count($keys), count(array_unique($keys)), 'each field appears once in the admin');
        $defaults = array_keys(PageTextData::DEFAULTS);
        sort($keys);
        sort($defaults);
        $this->assertSame($defaults, $keys, 'every default is editable and every field has a default');
    }
}
