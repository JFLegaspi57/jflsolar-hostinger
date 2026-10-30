<?php

namespace Tests\Feature;

use App\Support\Visitors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VisitorsTest extends TestCase
{
    use RefreshDatabase;

    private const UA = 'Mozilla/5.0 (Windows NT 10.0) Chrome/130';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('visitor-tables-v3');
    }

    public function test_only_browsers_that_run_the_page_script_are_counted(): void
    {
        $id = fn ($res) => preg_match("/var id = '([a-f0-9]{24})'/", $res->getContent(), $m) ? $m[1] : null;

        // A robot / uptime checker just loads the page: nothing is recorded.
        $this->withHeader('User-Agent', self::UA)->get('/')->assertOk();
        $this->assertSame(0, DB::table('visitor_days')->count());

        // A browser loads the page and its script checks in: counted once.
        $first = $this->withHeader('User-Agent', self::UA)->get('/');
        $vid = $first->getCookie(Visitors::COOKIE, false)->getValue();
        $this->post('/pv', ['id' => $id($first), 'start' => '1'])->assertNoContent();
        $this->post('/pv', ['id' => $id($first), 'start' => '1'])->assertNoContent();   // repeat: ignored
        $second = $this->withHeader('User-Agent', self::UA)->withUnencryptedCookie(Visitors::COOKIE, $vid)->get('/services');
        $this->post('/pv', ['id' => $id($second), 'start' => '1']);
        $this->post('/pv', ['id' => $id($second), 's' => '42']);
        $this->post('/pv', ['id' => str_repeat('0', 24), 'start' => '1']);              // made-up id: ignored

        // Robots by name and "Do Not Track" browsers are not even offered a check-in.
        $bot = $this->withHeader('User-Agent', 'Googlebot/2.1')->get('/about');
        $this->post('/pv', ['id' => $id($bot), 'start' => '1']);

        $this->assertSame(1, DB::table('visitor_ids')->count());
        $this->assertSame(1, DB::table('visitor_days')->count());
        $this->assertSame(['/', '/services'], DB::table('page_views')->orderBy('id')->pluck('path')->all());
        $this->assertSame(42, (int) DB::table('page_views')->where('path', '/services')->value('duration_s'));
    }

    public function test_new_and_returning_are_split_by_first_visit(): void
    {
        Visitors::ensureTables();
        $t = Visitors::today();
        DB::table('visitor_ids')->insert([
            ['id_hash' => str_repeat('a', 64), 'first_seen' => $t->subYears(2)->toDateString(), 'last_seen' => $t->toDateString(), 'days' => 2],
            ['id_hash' => str_repeat('b', 64), 'first_seen' => $t->toDateString(), 'last_seen' => $t->toDateString(), 'days' => 1],
        ]);
        DB::table('visitor_days')->insert([
            ['day' => $t->toDateString(), 'id_hash' => str_repeat('a', 64)],
            ['day' => $t->toDateString(), 'id_hash' => str_repeat('b', 64)],
        ]);

        $this->assertSame(['unique' => 2, 'new' => 1, 'returning' => 1], Visitors::between($t, $t));
    }

    public function test_visitors_page_needs_sign_in(): void
    {
        $this->get('/admin/visitors')->assertRedirect('/admin/login');
    }
}
