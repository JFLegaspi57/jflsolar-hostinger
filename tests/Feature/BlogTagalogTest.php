<?php

namespace Tests\Feature;

use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\BlogReaction;
use App\Models\Member;
use App\Models\User;
use App\Support\Blog;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTagalogTest extends TestCase
{
    use RefreshDatabase;

    private BlogPost $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
        config(['chatbot.api_key' => '']);
        Blog::ensureTables();
        $this->post = BlogPost::query()->create(['title' => 'Inside the factory', 'slug' => 'inside-the-factory', 'status' => 'published', 'published_at' => '2026-11-12 04:00:00',
            'excerpt' => 'English summary.', 'body' => "English opening.\nEnglish second paragraph.\nEnglish members part.",
            'title_tl' => 'Sa loob ng pabrika', 'excerpt_tl' => 'Buod sa Filipino.', 'body_tl' => "Panimula sa Filipino.\nIkalawang talata.\nBahagi para sa mga miyembro."]);
    }

    private function member(): Member
    {
        $m = new Member;
        $m->forceFill(['name' => 'Maria Santos', 'email' => 'maria@example.com', 'password' => 'sunny days on the roof', 'status' => 'active'])->save();

        return $m;
    }

    public function test_the_filipino_version_has_its_own_page_linked_both_ways(): void
    {
        $this->get('/blog/inside-the-factory')->assertOk()
            ->assertSee('href="/blog/inside-the-factory/tagalog"', false)->assertSee('Basahin sa Filipino')
            ->assertSee('hreflang="fil" href="'.\App\Support\Site::url('/blog/inside-the-factory/tagalog').'"', false);
        $this->get('/blog/inside-the-factory/tagalog')->assertOk()
            ->assertSee('<html lang="fil">', false)->assertSee('Sa loob ng pabrika')->assertSee('Panimula sa Filipino.')
            ->assertSee('12 Nobyembre 2026')->assertSee('Read in English')
            ->assertSee('Basahin ang buong post: libre para sa mga miyembro')
            ->assertSee('href="/signup?next=%2Fblog%2Finside-the-factory%2Ftagalog"', false)
            ->assertDontSee('Bahagi para sa mga miyembro.')->assertDontSee('English opening.')
            ->assertSee('Mga reaksyon at komento');
        $this->get('/blog')->assertSee('href="/blog/inside-the-factory/tagalog"', false);
        $this->get('/sitemap.xml')->assertSee('/blog/inside-the-factory/tagalog');
    }

    public function test_members_share_reactions_and_comments_between_both_versions(): void
    {
        $m = $this->member();
        $this->actingAs($m, 'member')->get('/blog/inside-the-factory/tagalog')->assertSee('Bahagi para sa mga miyembro.')
            ->assertSee('name="version" value="tagalog"', false);
        $this->actingAs($m, 'member')->post('/blog/inside-the-factory/react', ['reaction' => 'love', 'version' => 'tagalog'])
            ->assertRedirect('/blog/inside-the-factory/tagalog#comments');
        $this->actingAs($m, 'member')->post('/blog/inside-the-factory/comments', ['body' => 'Napakagandang post, salamat!', 'version' => 'tagalog'])
            ->assertRedirect('/blog/inside-the-factory/tagalog#comments');
        $this->assertSame(1, BlogReaction::query()->where('blog_post_id', $this->post->id)->count());
        $this->assertSame(1, BlogComment::query()->where('blog_post_id', $this->post->id)->count());
        // The same comment shows on the English post.
        $this->actingAs($m, 'member')->get('/blog/inside-the-factory')->assertSee('Napakagandang post, salamat!');
        $this->actingAs($m, 'member')->post('/blog/inside-the-factory/react', ['reaction' => 'love'])->assertRedirect('/blog/inside-the-factory#comments');
    }

    public function test_no_filipino_version_means_no_link_and_404(): void
    {
        BlogPost::query()->create(['title' => 'English only', 'slug' => 'english-only', 'status' => 'published', 'body' => 'Only in English for now, thanks.']);
        $this->get('/blog/english-only')->assertOk()->assertDontSee('Basahin sa Filipino');
        $this->get('/blog/english-only/tagalog')->assertNotFound();
    }

    public function test_ready_made_translation_files_exist_for_the_live_posts(): void
    {
        foreach (['who-is-jf-legaspi', 'jf-visit-to-jinko-solar', 'meeting-robi-balboa-goodwe-installer-summit-2026', 'jfl-solar-sustainable-energy-impact-award',
            'jf-visit-to-dyness', 'how-goodwe-makes-batteries', 'sungrow-geps-2026'] as $slug) {
            $tl = Blog::tagalog(new BlogPost(['slug' => $slug, 'title' => 'x', 'body' => 'x']));
            $this->assertNotNull($tl, $slug);
            $this->assertNotSame('', $tl['title']);
            $this->assertNotSame('', $tl['excerpt']);
            $this->assertGreaterThan(500, mb_strlen($tl['body']), $slug);
            $this->assertStringNotContainsStringIgnoringCase('solar system', $tl['body'], $slug);
        }
    }

    public function test_staff_can_edit_the_filipino_version_in_admin(): void
    {
        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($staff, 'web')->get('/admin/blog/'.$this->post->id)->assertOk()->assertSee('Filipino version (optional)')->assertSee('Panimula sa Filipino.');
        $this->actingAs($staff, 'web')->post('/admin/blog/'.$this->post->id, [
            'title' => $this->post->title, 'slug' => $this->post->slug, 'excerpt' => 'English summary.', 'body' => $this->post->body, 'published_on' => '2026-11-12',
            'title_tl' => 'Bagong pamagat', 'excerpt_tl' => '', 'body_tl' => 'Bagong panimula sa Filipino, mas mahaba.', 'lock_version' => $this->post->lock_version, 'intent' => 'publish',
        ]);
        $this->assertSame('Bagong pamagat', $this->post->refresh()->title_tl);
        auth('web')->logout();
        $this->get('/blog/inside-the-factory/tagalog')->assertSee('Bagong pamagat')->assertSee('Bagong panimula sa Filipino, mas mahaba.');
    }
}
