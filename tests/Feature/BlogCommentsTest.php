<?php

namespace Tests\Feature;

use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\Member;
use App\Models\User;
use App\Support\Blog;
use App\Support\BlogComments;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BlogCommentsTest extends TestCase
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
        $this->post = BlogPost::query()->create(['title' => 'Meeting Robi', 'slug' => 'meeting-robi', 'body' => 'Honored to meet Robi.', 'status' => 'published']);
    }

    private function member(string $name = 'Maria Dela Santos'): Member
    {
        $m = new Member;
        $m->forceFill(['name' => $name, 'email' => 'm'.uniqid().'@example.com', 'password' => 'sunny days on the roof', 'status' => 'active'])->save();

        return $m;
    }

    private function comment(Member $m, string $body)
    {
        return $this->actingAs($m, 'member')->post('/blog/meeting-robi/comments', ['body' => $body]);
    }

    public function test_guests_see_the_panel_and_are_asked_to_sign_in(): void
    {
        $this->get('/blog/meeting-robi')->assertOk()
            ->assertSee('Reactions &amp; comments', false)->assertSee('No comments yet')
            ->assertSee('href="/login?next=%2Fblog%2Fmeeting-robi"', false)->assertSee('create a free account');
        $this->post('/blog/meeting-robi/react', ['reaction' => 'love'])->assertRedirect('/login?next=%2Fblog%2Fmeeting-robi');
        $this->post('/blog/meeting-robi/comments', ['body' => 'Great!'])->assertRedirect('/login?next=%2Fblog%2Fmeeting-robi');
        $this->assertSame(0, BlogComment::query()->count());
    }

    public function test_members_react_change_and_remove_their_reaction(): void
    {
        $m = $this->member();
        $this->actingAs($m, 'member')->post('/blog/meeting-robi/react', ['reaction' => 'love'])->assertRedirect('/blog/meeting-robi#comments');
        $this->assertSame(1, BlogComments::counts($this->post)['love']);
        $this->actingAs($m, 'member')->get('/blog/meeting-robi')->assertSee('aria-pressed="true" title="Love"', false);
        $this->actingAs($m, 'member')->post('/blog/meeting-robi/react', ['reaction' => 'clap']);
        $this->assertSame([0, 1], [BlogComments::counts($this->post)['love'], BlogComments::counts($this->post)['clap']]);
        $this->actingAs($m, 'member')->post('/blog/meeting-robi/react', ['reaction' => 'clap']);
        $this->assertSame(0, array_sum(BlogComments::counts($this->post)));
        $this->actingAs($m, 'member')->post('/blog/meeting-robi/react', ['reaction' => 'angry'])->assertStatus(400);
    }

    public function test_only_positive_comments_are_published_and_others_wait_for_review(): void
    {
        $m = $this->member();
        $this->comment($m, 'Salamat, Robi! Very inspiring story.')->assertSessionHas('comment_status', 'Thank you! Your comment is posted.');
        $this->comment($m, 'This is a scam company.')->assertSessionHas('comment_status', 'Thank you! Your comment will appear once our team has reviewed it.');
        $this->comment($m, 'Not sure about this.');
        $this->comment($m, 'When is the next summit?');

        $this->assertSame(['published', 'review', 'review', 'review'], BlogComment::query()->orderBy('id')->pluck('status')->all());
        auth('member')->logout(); // visitors (not signed in)
        $this->get('/blog/meeting-robi')->assertSee('Very inspiring story.')->assertSee('Maria S.')
            ->assertDontSee('scam company')->assertDontSee('Not sure about this')->assertDontSee('next summit');
        // The author sees their own held comment, marked as waiting.
        $this->actingAs($m, 'member')->get('/blog/meeting-robi')->assertSee('scam company')->assertSee('Waiting for review');

        // Staff review: approve one, hide one.
        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($staff, 'web')->get('/admin')->assertSee('comments to review');
        $this->actingAs($staff, 'web')->get('/admin/blog')->assertSee('(3 waiting)');
        $this->actingAs($staff, 'web')->get('/admin/blog/comments')->assertOk()->assertSee('scam company')->assertSee('Held: contains rude or accusing words.')->assertSee($m->email);
        $question = BlogComment::query()->where('body', 'like', '%next summit%')->firstOrFail();
        $scam = BlogComment::query()->where('body', 'like', '%scam%')->firstOrFail();
        $this->actingAs($staff, 'web')->post('/admin/blog/comments/'.$question->id, ['action' => 'approve'])->assertRedirect('/admin/blog/comments');
        $this->actingAs($staff, 'web')->post('/admin/blog/comments/'.$scam->id, ['action' => 'hide']);
        auth('web')->logout();
        $this->get('/blog/meeting-robi')->assertSee('next summit')->assertDontSee('scam company');
        $this->assertSame('hidden', $scam->fresh()->status);
    }

    public function test_comment_rules_links_spam_and_limits(): void
    {
        $m = $this->member();
        $this->comment($m, 'Great! see https://spam.example')->assertSessionHasErrors('body');
        $this->comment($m, '')->assertSessionHasErrors('body');
        $this->comment($m, str_repeat('great ', 200))->assertSessionHasErrors('body');
        $this->actingAs($m, 'member')->post('/blog/meeting-robi/comments', ['body' => 'Great post', 'website' => 'x']);
        $this->assertSame(0, BlogComment::query()->count());
        $this->comment($m, '<script>alert(1)</script> Great!');
        $this->get('/blog/meeting-robi')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_claude_decides_when_the_api_key_is_set(): void
    {
        config(['chatbot.enabled' => true, 'chatbot.api_key' => 'test-key']);
        $m = $this->member();
        $answer = 'positive';
        Http::fake(['api.anthropic.com/*' => function () use (&$answer) {
            return Http::response(['content' => [['type' => 'text', 'text' => $answer]]]);
        }]);
        $this->comment($m, 'No doubt, the best story I read this year.');
        $answer = 'negative';
        $this->comment($m, 'Great... if you like overpriced panels.');
        $this->assertSame(['published', 'review'], BlogComment::query()->orderBy('id')->pluck('status')->all());
        $this->assertSame('Held: Claude judged it negative.', BlogComment::query()->orderByDesc('id')->value('check_note'));
        // Rude words are held without asking Claude.
        $answer = 'positive';
        $this->comment($m, 'Gago, great post');
        $this->assertSame('review', BlogComment::query()->orderByDesc('id')->value('status'));
    }
}
