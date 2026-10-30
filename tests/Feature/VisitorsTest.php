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
        Cache::forget('visitor-tables-v1');
    }

    public function test_a_browser_is_counted_once_a_day_and_robots_are_not(): void
    {
        $first = $this->withHeader('User-Agent', self::UA)->get('/');
        $first->assertOk()->assertCookie(Visitors::COOKIE);
        $vid = $first->getCookie(Visitors::COOKIE, false)->getValue();

        $this->withHeader('User-Agent', self::UA)->withUnencryptedCookie(Visitors::COOKIE, $vid)->get('/services')->assertOk();
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get('/about')->assertOk();
        $this->withHeaders(['User-Agent' => self::UA, 'DNT' => '1'])->get('/about')->assertOk();

        $this->assertSame(1, DB::table('visitor_ids')->count());
        $this->assertSame(1, DB::table('visitor_days')->count());
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
