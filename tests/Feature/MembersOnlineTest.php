<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Support\Members;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MembersOnlineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
        Http::fake();
        Members::flushState();
        Members::ensureTables();
    }

    private function member(string $name, string $email): Member
    {
        return Member::query()->create(['name' => $name, 'email' => $email, 'password' => 'sunny days on the roof', 'status' => 'active']);
    }

    public function test_staff_see_which_members_are_online_and_the_public_does_not(): void
    {
        $maria = $this->member('Maria Santos', 'maria@example.com');
        $jose = $this->member('Jose Cruz', 'jose@example.com');

        // Maria opens a page now; Jose was last seen 40 minutes ago.
        $this->post('/login', ['email' => 'maria@example.com', 'password' => 'sunny days on the roof']);
        $this->get('/faq')->assertOk();
        DB::table('member_activity')->insert(['member_id' => $jose->id, 'kind' => 'page', 'path' => '/forum', 'created_at' => now()->subMinutes(40)]);

        $online = Members::online();
        $this->assertSame(['maria@example.com'], $online->pluck('email')->all());
        $this->assertSame('/faq', $online->first()->last_path);

        // Public pages never show it.
        $this->get('/blog')->assertDontSee('Online now');
        $this->post('/logout');

        $staff = User::query()->create(['name' => 'Editor', 'email' => 'editor@jflsolar.test', 'password' => 'a long test password', 'role' => 'editor', 'active' => true, 'must_change_password' => false]);
        $html = $this->actingAs($staff, 'web')->get('/admin/members')->assertOk()
            ->assertSee('Online now')->assertSee('maria@example.com')->assertSee('Viewing')->assertSee('href="/blog"', false)
            ->getContent();
        $this->assertMatchesRegularExpression('/Online now <span class="tabular text-steel-600">\(1\)<\/span>/', $html);
        $this->assertStringContainsString('1<span class="sr-only"> online now</span>', $html);

        // A disabled account is not listed.
        $maria->forceFill(['status' => 'disabled'])->save();
        $this->assertTrue(Members::online()->isEmpty());
        $this->actingAs($staff, 'web')->get('/admin/members')->assertSee('No members are online right now.');
    }
}
