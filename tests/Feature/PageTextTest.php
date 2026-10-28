<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PageTextController;
use App\Models\PageText as PageTextRow;
use App\Models\User;
use App\Support\PageText;
use App\Support\PageTextData;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    private function staff(): User
    {
        return User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
    }

    /** Every field of a page with its current value, as the form submits it. */
    private function formFor(string $page, array $changes = []): array
    {
        $def = PageText::page($page);
        $loaded = PageText::load($def);
        $data = ['version' => $loaded['version']];
        foreach (PageText::effective($def, $loaded['rows']) as $k => $v) {
            $data[PageTextController::fieldName($k)] = $changes[$k] ?? $v;
        }

        return $data;
    }

    public function test_pages_show_the_original_wording_by_default(): void
    {
        $this->get('/')->assertOk()->assertSee(PageTextData::DEFAULTS['home.hero.title']);
        $this->get('/services')->assertOk()->assertSee(PageTextData::DEFAULTS['services.step1.title']);
        $this->get('/about')->assertOk()->assertSee(PageTextData::DEFAULTS['about.why.title']);
    }

    public function test_staff_edit_shows_on_both_pages_and_an_empty_box_restores_the_original(): void
    {
        $user = $this->staff();
        $this->actingAs($user)->get('/admin/text/services')->assertOk()->assertSee('Step 1 — title');

        $this->actingAs($user)->post('/admin/text/services', $this->formFor('services', ['services.step1.title' => 'Free consultation']))
            ->assertRedirect('/admin/text/services')->assertSessionHas('status');
        $this->assertSame('Free consultation', PageTextRow::query()->find('services.step1.title')?->value);
        $this->assertSame(1, PageTextRow::query()->count(), 'only changed fields are stored');
        $this->get('/services')->assertSee('Free consultation');
        $this->get('/')->assertSee('Free consultation'); // the steps appear on the home page too

        $this->actingAs($user)->post('/admin/text/services', $this->formFor('services', ['services.step1.title' => '  ']))->assertRedirect('/admin/text/services');
        $this->assertSame(0, PageTextRow::query()->count());
        $this->get('/services')->assertSee(PageTextData::DEFAULTS['services.step1.title'])->assertDontSee('Free consultation');
    }

    public function test_list_fields_become_bullet_points(): void
    {
        $user = $this->staff();
        $this->actingAs($user)->post('/admin/text/home', $this->formFor('home', ['home.path1.points' => "- Free site visit\n\n• Design\nInstallation"]));
        $this->assertSame("Free site visit\nDesign\nInstallation", PageText::load(PageText::page('home'))['rows']['home.path1.points']);
        $this->get('/')->assertSee('Free site visit');
    }

    public function test_a_save_based_on_an_old_version_is_refused(): void
    {
        $user = $this->staff();
        $stale = $this->formFor('about');
        $this->actingAs($user)->post('/admin/text/about', $this->formFor('about', ['about.why.title' => 'First edit']));
        $stale[PageTextController::fieldName('about.why.title')] = 'Second edit';
        $this->actingAs($user)->post('/admin/text/about', $stale)->assertSessionHas('form_error');
        $this->assertSame('First edit', PageTextRow::query()->find('about.why.title')?->value);

        // The redisplayed form keeps the old version with the restored values, so saving
        // again is still refused instead of overwriting the first edit.
        $html = $this->actingAs($user)->get('/admin/text/about')->assertOk()->getContent();
        $this->assertStringContainsString('name="version" value="'.$stale['version'].'"', $html);
        $this->assertStringContainsString('Second edit', $html);
        $this->actingAs($user)->post('/admin/text/about', $stale)->assertSessionHas('form_error');
        $this->assertSame('First edit', PageTextRow::query()->find('about.why.title')?->value);
    }

    public function test_too_long_text_is_rejected_and_nothing_is_saved(): void
    {
        $user = $this->staff();
        $this->actingAs($user)->post('/admin/text/home', $this->formFor('home', ['home.hero.title' => str_repeat('x', 161)]))
            ->assertSessionHasErrors(PageTextController::fieldName('home.hero.title'));
        $this->assertSame(0, PageTextRow::query()->count());
    }

    public function test_visitors_cannot_edit_and_unknown_pages_are_not_found(): void
    {
        $this->post('/admin/text/home', ['version' => 'none'])->assertRedirect('/admin/login');
        $this->actingAs($this->staff())->get('/admin/text/contact')->assertNotFound();
    }

    public function test_package_cards_show_photos_only_on_the_packages_page(): void
    {
        $this->get('/packages')->assertOk()->assertSee('aspect-[16/10]', false);
        $this->get('/')->assertOk()->assertDontSee('aspect-[16/10]', false);
    }

    public function test_header_no_longer_shows_the_phone_number_next_to_the_navigation(): void
    {
        $html = $this->get('/about')->getContent();
        $desktopBar = substr($html, strpos($html, '<header'), strpos($html, 'data-open-dialog="mobile-menu"') - strpos($html, '<header'));
        $this->assertStringNotContainsString('tel:', $desktopBar);
    }
}
