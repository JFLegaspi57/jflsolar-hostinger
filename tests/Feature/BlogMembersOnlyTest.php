<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Member;
use App\Models\User;
use App\Support\Blog;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogMembersOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
        Blog::ensureTables();
        BlogPost::query()->create(['title' => 'Inside the factory', 'slug' => 'inside-the-factory', 'status' => 'published',
            'excerpt' => 'A short summary for the blog list.',
            'body' => "First paragraph about the visit.\n## The line\nSecond paragraph about the line.\nThird paragraph, members only.\n- A list item for members"]);
    }

    public function test_visitors_see_the_opening_and_an_invitation_to_sign_up(): void
    {
        $this->get('/blog/inside-the-factory')->assertOk()
            ->assertSee('First paragraph about the visit.')->assertSee('The line')->assertSee('Second paragraph about the line.')
            ->assertDontSee('Third paragraph, members only.')->assertDontSee('A list item for members')
            ->assertSee('Read the full post: free for members')
            ->assertSee('href="/signup?next=%2Fblog%2Finside-the-factory"', false)
            ->assertSee('href="/login?next=%2Fblog%2Finside-the-factory"', false)
            ->assertSee('"isAccessibleForFree":false', false);
        // The blog list still shows the excerpt to everyone.
        $this->get('/blog')->assertOk()->assertSee('A short summary for the blog list.');
    }

    public function test_members_and_staff_read_the_whole_post(): void
    {
        $m = new Member;
        $m->forceFill(['name' => 'Maria Santos', 'email' => 'maria@example.com', 'password' => 'sunny days on the roof', 'status' => 'active'])->save();
        $this->actingAs($m, 'member')->get('/blog/inside-the-factory')->assertOk()
            ->assertSee('Third paragraph, members only.')->assertSee('A list item for members')->assertDontSee('Read the full post: free for members');
        auth('member')->logout();

        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($staff, 'web')->get('/blog/inside-the-factory')->assertOk()->assertSee('Third paragraph, members only.');
    }
}
