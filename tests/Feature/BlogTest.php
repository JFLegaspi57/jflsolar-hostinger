<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BlogTest extends TestCase
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

    public function test_founder_section_links_to_an_empty_blog(): void
    {
        $this->get('/about')->assertOk()->assertSee('href="/blog"', false)->assertSee('Blog Posts')->assertDontSee('Read My Blog');
        $this->get('/blog')->assertOk()->assertSee('Blog by JF Legaspi', false)->assertSee('The first post is on the way.')
            ->assertSee('src="/images/blog-summit-1280.jpg"', false)->assertSee('/images/blog-summit-640.webp 640w', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/blog');
        // "Blog Posts" sits in the About drop-down, and About is highlighted on blog pages.
        $this->get('/')->assertSeeInOrder(['href="/about"', 'href="/contact"', 'href="/blog"', 'Blog Posts'], false);
        $this->get('/blog')->assertSee('aria-current="page"', false);
    }

    public function test_staff_write_publish_and_unpublish_a_post_with_a_cover_image(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff, 'web')->get('/admin/blog')->assertOk()->assertSee('No posts yet');
        $this->actingAs($staff, 'web')->get('/admin')->assertSee('href="/admin/blog"', false);

        // A new post starts as a draft and is hidden.
        $this->actingAs($staff, 'web')->post('/admin/blog', [
            'title' => 'Why we size solar PV systems around daytime use',
            'excerpt' => 'Solar you use yourself saves more than solar you export.',
            'body' => "Most homes use the most power at night.\n## What you can do\n- Run the washing machine at noon\nTip: check your bill first.",
        ]);
        $post = BlogPost::query()->firstOrFail();
        $this->assertSame('draft', $post->status);
        $this->assertNull($post->published_at);
        $this->get('/blog/'.$post->slug)->assertNotFound();

        // Cover image upload, hidden while the post is a draft.
        $this->actingAs($staff, 'web')->post('/admin/media/upload/post/'.$post->id, ['alt' => 'Panels on a roof at noon', 'file' => UploadedFile::fake()->image('roof.jpg', 1600, 1000)]);
        $this->actingAs($staff, 'web')->post('/admin/media/upload/post/'.$post->id, ['alt' => 'JF at the airport', 'file' => UploadedFile::fake()->image('airport.jpg', 600, 1000)]);
        $img = MediaAsset::query()->where('blog_post_id', $post->id)->where('is_cover', true)->firstOrFail();
        $this->assertTrue($img->is_cover);
        $this->get($img->url($img->bestWidth(1280)))->assertNotFound();

        // Publish.
        $this->actingAs($staff, 'web')->post('/admin/blog/'.$post->id, [
            'title' => $post->title, 'slug' => $post->slug, 'excerpt' => $post->excerpt, 'body' => $post->body,
            'lock_version' => $post->lock_version, 'intent' => 'publish',
        ]);
        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);

        // The blog page banner is now one of the post's photos (random from all published posts).
        $this->get('/blog')->assertSee('/media/blog/'.$post->id.'/', false)->assertDontSee('src="/images/blog-summit-1280.jpg"', false);
        $this->get('/blog')->assertOk()->assertSee('Why we size solar PV systems around daytime use')->assertSee('Solar you use yourself saves more')
            ->assertDontSee('The first post is on the way.');
        $this->get('/blog/'.$post->slug)->assertOk()
            ->assertDontSee('By <a href="/about"', false)->assertDontSee('By JF Legaspi')
            ->assertSee('<h2 class="text-2xl font-extrabold text-steel-950" style="padding-top: .75rem">What you can do</h2>', false)->assertSee('Run the washing machine at noon')->assertDontSee('>Request a Quote</a>\n            </div>', false)->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('alt="Panels on a roof at noon"', false)
            ->assertSee('aria-label="Photos"', false)->assertSee('alt="JF at the airport"', false)
            ->assertSee('<dialog id="photo-viewer"', false)->assertSee('data-photo="0"', false)->assertSee('data-photo="1"', false)
            ->assertSee('id="page-hero-title"', false)->assertSee('oncontextmenu="return false"', false)
            ->assertSee('draggable="false"', false)->assertDontSee('target="_blank" rel="noopener" class="relative block', false);
        $this->get($img->url($img->bestWidth(1280)))->assertOk();
        $this->get('/sitemap.xml')->assertSee('/blog/'.$post->slug);

        // Unpublish from the list hides it again.
        $this->actingAs($staff, 'web')->post('/admin/blog/'.$post->id.'/status', ['status' => 'draft']);
        $this->get('/blog/'.$post->slug)->assertNotFound();
        $this->get('/blog')->assertSee('The first post is on the way.');
    }

    public function test_post_date_can_be_set_and_orders_the_list(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff, 'web')->post('/admin/blog', ['title' => 'Newer post', 'body' => 'A post written today about solar PV systems.', 'intent' => 'publish']);
        $this->actingAs($staff, 'web')->post('/admin/blog', ['title' => 'Who is JF Legaspi?', 'body' => 'An introduction written for the blog.', 'published_on' => '2024-06-01', 'intent' => 'publish']);
        $old = BlogPost::query()->where('title', 'Who is JF Legaspi?')->firstOrFail();
        $this->assertSame('2024-06-01', $old->published_at->timezone('Asia/Manila')->toDateString());
        $this->get('/blog/'.$old->slug)->assertSee('1 June 2024')->assertSee('datetime="2024-06-01"', false);
        $this->get('/blog')->assertSeeInOrder(['Newer post', 'Who is JF Legaspi?']);
        $this->actingAs($staff, 'web')->get('/admin/blog/'.$old->id)->assertSee('value="2024-06-01"', false);

        // Editing without a date keeps it; a bad date is refused.
        $this->actingAs($staff, 'web')->post('/admin/blog/'.$old->id, ['title' => $old->title, 'slug' => $old->slug, 'body' => $old->body, 'published_on' => '1990-01-01', 'lock_version' => $old->lock_version])
            ->assertSessionHasErrors('published_on');
    }

    public function test_posts_show_no_author_byline(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff, 'web')->post('/admin/blog', ['title' => 'Who is JF Legaspi?', 'body' => 'JF Legaspi is a Filipino engineer.', 'published_on' => '2024-06-01', 'intent' => 'publish']);
        $post = BlogPost::query()->firstOrFail();
        $this->get('/blog/'.$post->slug)->assertOk()
            ->assertSee('<p class="text-sm font-semibold text-steel-600"><time datetime="2024-06-01">1 June 2024</time></p>', false)
            ->assertDontSee('>By ', false);
        $this->actingAs($staff, 'web')->get('/admin/blog/'.$post->id)->assertDontSee('name="author"', false);
    }

    public function test_post_links_are_rendered_safely(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff, 'web')->post('/admin/blog', ['title' => 'Meeting Robi', 'intent' => 'publish',
            'body' => "Honored to meet [Robi Balboa](https://web.facebook.com/robi.balboa12) at the summit!\nNot a link: [x](javascript:alert(1)) and <b>bold</b>"]);
        $post = BlogPost::query()->firstOrFail();
        $this->get('/blog/'.$post->slug)->assertOk()
            ->assertSee('Honored to meet <a href="https://web.facebook.com/robi.balboa12" target="_blank" rel="noopener nofollow"', false)
            ->assertSee('>Robi Balboa<span class="sr-only"> (opens in a new tab)</span></a> at the summit!', false)
            ->assertDontSee('href="javascript', false)->assertDontSee('<b>bold</b>', false)->assertSee('&lt;b&gt;bold&lt;/b&gt;', false);
    }

    public function test_post_needs_a_title_and_body(): void
    {
        $this->actingAs($this->staff(), 'web')->post('/admin/blog', ['title' => '', 'body' => 'short'])
            ->assertSessionHasErrors(['title', 'body']);
        $this->assertSame(0, BlogPost::query()->count());
    }
}
