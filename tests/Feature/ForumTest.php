<?php

namespace Tests\Feature;

use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\Member;
use App\Models\User;
use App\Support\Forum;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    private function member(array $attrs = []): Member
    {
        $m = new Member;
        $m->forceFill(array_merge(['name' => 'Maria Dela Santos', 'email' => 'maria'.uniqid().'@example.com', 'password' => 'sunny days on the roof', 'status' => 'active'], $attrs))->save();

        return $m;
    }

    private function staff(): User
    {
        return User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
    }

    private function ask(array $changes = [])
    {
        return $this->post('/forum', array_merge(['category' => 'batteries', 'title' => 'Which battery for a 6 kW hybrid inverter?', 'body' => "I have 10 panels and a 6 kW hybrid.\nWhat battery size do you suggest?"], $changes));
    }

    public function test_reading_is_open_and_posting_needs_an_account(): void
    {
        $this->get('/forum')->assertOk()->assertSee('Solar forum')->assertSee('Be the first to ask')->assertSee('create a free account');
        $this->get('/')->assertSee('href="/forum"', false);
        $this->get('/forum/new')->assertRedirect('/login?next=%2Fforum%2Fnew');
        $this->ask()->assertRedirect('/login?next=%2Fforum%2Fnew');
        $this->assertFalse(Forum::hasTables() && ForumTopic::query()->exists());
    }

    public function test_a_new_members_first_post_waits_for_approval_then_they_are_trusted(): void
    {
        $m = $this->member();
        $this->actingAs($m, 'member');
        $this->get('/forum/new')->assertOk()->assertSee('Posting as')->assertSee('Maria S.')->assertSee('first post appears after a quick check');
        $response = $this->ask();
        $topic = ForumTopic::query()->sole();
        $response->assertRedirect($topic->path());
        $this->assertSame('pending', $topic->status);
        $this->assertSame('Maria S.', $topic->author_name);   // first name + last initial only

        // The author sees it, labelled; the public does not.
        $this->get($topic->path())->assertOk()->assertSee('Waiting for approval')->assertSee('What battery size');
        $this->get('/forum')->assertDontSee('Which battery for a 6 kW');
        auth('member')->logout();
        $this->get($topic->path())->assertNotFound();

        // Staff approve it from the admin: visible, and the member is trusted from now on.
        $staff = $this->staff();
        $this->actingAs($staff, 'web')->get('/admin/forum')->assertOk()->assertSee('Which battery for a 6 kW')->assertSee($m->email);
        $post = ForumPost::query()->sole();
        $this->actingAs($staff, 'web')->post('/admin/forum/posts/'.$post->id, ['action' => 'approve'])->assertRedirect('/admin/forum');
        $this->assertSame('published', $topic->fresh()->status);
        $this->assertTrue($m->fresh()->forum_trusted);
        auth('web')->logout();
        $this->get('/forum')->assertSee('Which battery for a 6 kW')->assertSee('Maria S.');
        $this->get($topic->path())->assertOk()->assertDontSee('Waiting for approval')->assertDontSee($m->email);

        // Trusted: the next reply appears at once.
        $this->actingAs($m->fresh(), 'member')->post($topic->path().'/reply', ['body' => 'Update: I went with 16 kWh, thanks!'])->assertSessionHas('forum_status', 'Your reply is posted.');
        $this->assertSame(1, $topic->fresh()->replies_count);
    }

    public function test_staff_answer_as_the_jfl_solar_team_and_moderate_topics(): void
    {
        $m = $this->member(['forum_trusted' => true]);
        $this->actingAs($m, 'member')->ask();
        $topic = ForumTopic::query()->sole();
        $this->assertSame('published', $topic->status);
        auth('member')->logout();

        $staff = $this->staff();
        $this->actingAs($staff, 'web')->post($topic->path().'/reply', ['body' => 'A 16 kWh battery suits most homes with that setup.'])->assertRedirect();
        $reply = ForumPost::query()->where('is_opening', false)->sole();
        $this->assertSame('JFL Solar team', $reply->author_name);
        $this->assertSame('published', $reply->status);
        $this->actingAs($staff, 'web')->get($topic->path())->assertSee('Staff')->assertSee('Pin to top')->assertSee('Close replies');

        $this->actingAs($staff, 'web')->post('/admin/forum/topics/'.$topic->id, ['action' => 'lock', 'back' => $topic->path()])->assertRedirect($topic->path());
        $this->assertTrue($topic->fresh()->locked);
        auth('web')->logout();
        $this->actingAs($m, 'member')->post($topic->path().'/reply', ['body' => 'One more question please...'])->assertSessionHas('forum_error');

        $this->actingAs($staff, 'web')->post('/admin/forum/topics/'.$topic->id, ['action' => 'hide', 'back' => 'https://evil.example'])->assertRedirect('/admin/forum');
        auth('web')->logout();
        auth('member')->logout();
        $this->get($topic->path())->assertNotFound();
    }

    public function test_posts_are_plain_text_with_safe_nofollow_links_and_limits(): void
    {
        $m = $this->member(['forum_trusted' => true]);
        $this->actingAs($m, 'member');
        $this->ask(['body' => '<script>alert(1)</script> see https://example.com/spec.pdf" onmouseover="x and https://example.org.'])->assertRedirect();
        $topic = ForumTopic::query()->sole();
        $html = $this->get($topic->path())->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('rel="nofollow ugc noopener"', $html);
        $this->assertStringNotContainsString('" onmouseover="x', $html);
        $this->assertStringContainsString('href="https://example.org"', $html);   // trailing dot not part of the link

        $this->ask(['title' => 'Spam spam spam spam', 'body' => 'http://a.com http://b.com http://c.com http://d.com'])->assertSessionHasErrors('body');
        $this->ask(['title' => 'short'])->assertSessionHasErrors('title');
        $this->ask(['title' => 'Visit https://spam.example now'])->assertSessionHasErrors('title');
        $this->ask(['category' => 'nope'])->assertSessionHasErrors('category');
        $this->assertSame(1, ForumTopic::query()->count());
    }

    public function test_members_are_slowed_down_after_many_posts(): void
    {
        $m = $this->member(['forum_trusted' => true]);
        $this->actingAs($m, 'member');
        for ($i = 1; $i <= 5; $i++) {
            $this->ask(['title' => 'Question number '.$i.' about batteries']);
        }
        $this->ask(['title' => 'Question number 6 about batteries'])->assertSessionHasErrors('body');
        $this->assertSame(5, ForumTopic::query()->count());
    }

    public function test_published_topics_are_in_the_sitemap_and_search_works(): void
    {
        $m = $this->member(['forum_trusted' => true]);
        $this->actingAs($m, 'member')->ask();
        $this->actingAs($m, 'member')->ask(['title' => 'Net metering application in Cavite', 'category' => 'grid']);
        $this->get('/forum?q=metering')->assertSee('Net metering application')->assertDontSee('Which battery for');
        $this->get('/forum?category=batteries')->assertSee('Which battery for')->assertDontSee('Net metering application');
        $topic = ForumTopic::query()->where('category', 'grid')->sole();
        $this->get('/sitemap.xml')->assertSee('/forum/'.$topic->slug, false);
    }

    public function test_disabled_members_cannot_post(): void
    {
        $m = $this->member(['forum_trusted' => true, 'status' => 'disabled']);
        $this->actingAs($m, 'member')->ask()->assertRedirect('/login?next=%2Fforum%2Fnew');
        $this->assertFalse(Forum::hasTables() && ForumTopic::query()->exists());
    }
}
