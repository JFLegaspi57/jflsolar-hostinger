<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Support\Lang;
use App\Support\ProjectTl;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProjectTagalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
        ProjectTl::ensureColumns();
        PublicContent::flush();
    }

    private function project(array $extra = []): Project
    {
        $p = Project::query()->create($extra + ['title' => 'Home system', 'slug' => 'home-system', 'project_type' => 'residential', 'status' => 'published',
            'summary' => 'English summary.', 'description' => "English story.\n## The Array\nEnglish details.", 'is_sample' => false, 'sort_order' => 999]);
        PublicContent::flush();

        return $p;
    }

    public function test_filipino_version_and_language_links(): void
    {
        $this->project(['title_tl' => 'Sistema sa bahay', 'summary_tl' => 'Buod.', 'description_tl' => "Kuwento sa Filipino.\n## Ang Array\nMga detalye."]);

        $this->get('/projects/home-system')->assertOk()->assertSee('Basahin sa Filipino')->assertSee('hreflang="fil"', false);
        $this->get('/projects/home-system/tagalog')->assertOk()->assertSee('<html lang="fil">', false)
            ->assertSee('Sistema sa bahay')->assertSee('Kuwento sa Filipino.')->assertSee('Read in English')->assertSee('Residensyal')->assertDontSee('English story.');

        $this->withCookie(Lang::COOKIE, 'fil')->get('/projects/home-system')->assertRedirect('/projects/home-system/tagalog');
        $this->get('/lang/en?back='.rawurlencode('/projects/home-system/tagalog'))->assertRedirect('/projects/home-system');
    }

    public function test_admin_form_shows_and_keeps_the_filipino_text(): void
    {
        $p = $this->project(['title_tl' => 'Sistema sa bahay', 'summary_tl' => 'Buod.', 'description_tl' => 'Kuwento sa Filipino.']);
        $owner = \App\Models\User::query()->create(['name' => 'Owner', 'email' => 'owner-tl@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($owner, 'web')->get('/admin/projects/'.$p->id)->assertOk()->assertSee('Kuwento sa Filipino.')->assertSee('Sistema sa bahay');
    }

    public function test_project_without_filipino_text_stays_english(): void
    {
        $this->project();

        $this->get('/projects/home-system')->assertOk()->assertDontSee('Basahin sa Filipino');
        $this->get('/projects/home-system/tagalog')->assertNotFound();
        $this->withCookie(Lang::COOKIE, 'fil')->get('/projects/home-system')->assertOk();
    }
}
