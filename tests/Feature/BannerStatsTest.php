<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Support\PublicContent;
use App\Support\Visitors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BannerStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PublicContent::flush();
    }

    public function test_homepage_banner_shows_live_visitor_numbers(): void
    {
        Visitors::ensureTables();
        DB::table('visitor_ids')->insert([
            ['id_hash' => str_repeat('a', 64), 'first_seen' => '2026-10-01', 'last_seen' => '2026-10-31', 'days' => 3],
            ['id_hash' => str_repeat('b', 64), 'first_seen' => '2026-10-31', 'last_seen' => '2026-10-31', 'days' => 1],
        ]);
        $today = Visitors::today()->toDateString();
        DB::table('visitor_days')->insert([
            ['day' => '2026-10-01', 'id_hash' => str_repeat('a', 64)], ['day' => '2026-10-15', 'id_hash' => str_repeat('a', 64)],
            ['day' => $today, 'id_hash' => str_repeat('a', 64)], ['day' => $today, 'id_hash' => str_repeat('b', 64)],
        ]);
        $this->get('/login');   // creates the member tables
        \App\Support\Members::ensureTables();
        (new Member)->forceFill(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x long password', 'status' => 'active'])->save();
        (new Member)->forceFill(['name' => 'B', 'email' => 'b@example.com', 'password' => 'x long password', 'status' => 'disabled'])->save();
        \App\Support\Newsletter::subscribe('s1@example.com', null, 'test');
        \App\Support\Newsletter::subscribe('s2@example.com', null, 'test');
        \App\Support\Newsletter::unsubscribeEmail('s2@example.com');
        \App\Support\CourseResults::seen((int) Member::query()->where('email', 'a@example.com')->value('id'), 1);
        Cache::forget('public-visitor-stats-v3');

        $page = $this->get('/')->assertOk()->assertSee('First-time visitors')->assertSee('Returning visitors')->assertSee('Total visits')->assertSee('Today’s visitors', false)->assertSee('id="live-stats"', false);
        $this->assertMatchesRegularExpression('/data-stat="today"[^>]*>2</', $page->getContent());
        $this->assertMatchesRegularExpression('/data-stat="first"[^>]*>2</', $page->getContent());
        $this->assertMatchesRegularExpression('/data-stat="returning"[^>]*>1</', $page->getContent());
        $page->assertSee('Online Course Students')->assertSee('id="live-stats-2"', false);
        $this->assertMatchesRegularExpression('/data-stat="members"[^>]*>1</', $page->getContent());
        $this->assertMatchesRegularExpression('/data-stat="subscribers"[^>]*>1</', $page->getContent());
        $this->assertMatchesRegularExpression('/data-stat="students"[^>]*>1</', $page->getContent());
        $this->assertMatchesRegularExpression('/data-stat="visits"[^>]*>4</', $page->getContent());

        $this->getJson('/stats/visitors')->assertOk()->assertExactJson(['today' => 2, 'first' => 2, 'returning' => 1, 'visits' => 4, 'members' => 1, 'subscribers' => 1, 'students' => 1])->assertHeader('Cache-Control', 'no-store, private');
    }
}
