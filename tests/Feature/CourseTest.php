<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Support\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsk_test_course';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
        @unlink(storage_path('app/course-hidden-members.json'));   // no member hidden from the course numbers
        config(['shop.paymongo.secret_key' => 'sk_test_abc', 'shop.paymongo.webhook_secret' => self::SECRET]);
    }

    private function member(string $email = 'maria@example.com'): Member
    {
        return Member::query()->create(['name' => 'Maria Santos', 'email' => $email, 'phone' => '0917 555 1234', 'password' => 'a long test password', 'status' => 'active']);
    }

    private function owner(): User
    {
        return User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
    }

    private function lesson(string $status = 'published'): object
    {
        Course::ensureTables();
        $id = DB::table('course_lessons')->insertGetId(['slug' => 'understanding-solar-energy', 'title' => 'Understanding Solar Energy', 'summary' => 'Sun to Earth', 'body' => '<h2>Secret notes</h2><p>1,377 W/m²</p><script>alert(1)</script>[[slide:1]]', 'position' => 1, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
        $img = imagecreatetruecolor(40, 20);
        $tmp = tempnam(sys_get_temp_dir(), 'sl').'.png';
        imagepng($img, $tmp);
        $this->assertNull(Course::addSlide($id, new UploadedFile($tmp, 'slide1.png', 'image/png', null, true)));

        return DB::table('course_lessons')->where('id', $id)->first();
    }

    private function webhook(string $ref, int $centavos, string $session = 'cs_mem_1'): \Illuminate\Testing\TestResponse
    {
        $raw = json_encode(['data' => ['id' => 'evt_1', 'type' => 'event', 'attributes' => [
            'type' => 'checkout_session.payment.paid', 'livemode' => false,
            'data' => ['id' => $session, 'type' => 'checkout_session', 'attributes' => [
                'reference_number' => $ref,
                'payments' => [['id' => 'pay_1', 'attributes' => ['amount' => $centavos, 'status' => 'paid', 'source' => ['type' => 'gcash']]]],
            ]],
        ]]]);
        $t = time();
        $sig = hash_hmac('sha256', $t.'.'.$raw, self::SECRET);

        return $this->call('POST', '/webhooks/paymongo', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => "t={$t},te={$sig},li="], $raw);
    }

    public function test_lessons_are_free_for_signed_in_members(): void
    {
        $lesson = $this->lesson();
        $slide = DB::table('course_slides')->first();
        $url = Course::PATH.'/'.$lesson->slug;

        // Visitors see the course page and lesson titles, never the lesson text or slides.
        $this->get(Course::PATH)->assertOk()->assertSee('Free for JFL Solar members')->assertDontSee('Understanding Solar Energy')->assertDontSee($lesson->slug, false)->assertDontSee('Secret notes')->assertSee('Sign in or create a free account to see the lessons')->assertSee('Create a free account');
        $this->get($url)->assertRedirect(Course::PATH);
        $this->get('/courses/slide/'.$slide->id)->assertForbidden();

        // Any signed-in member: full lesson, watermark, no script, not cached.
        $m = $this->member();
        $this->actingAs($m, 'member');
        $this->get(Course::PATH)->assertSee('all lessons are open')->assertSee('Understanding Solar Energy');
        $res = $this->get($url)->assertOk()->assertSee('Secret notes')->assertSee('/courses/slide/'.$slide->id, false)->assertSee('JFL Solar &amp; Renewables Corp', false)->assertSee('lesson-shield', false)->assertDontSee('alert(1)', false);
        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));
        $this->assertStringContainsString('noindex', (string) $res->headers->get('X-Robots-Tag'));
        $this->get('/courses/slide/'.$slide->id)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get('/account')->assertSee('The Fundamentals of Solar PV System');
        if (now('Asia/Manila')->lt(\Illuminate\Support\Carbon::parse('2026-12-04', 'Asia/Manila'))) {
            $this->get('/account')->assertSee('Fixed: "Check my answer" in the Online Course', false);
        }

        // A disabled account: locked.
        $m->forceFill(['status' => 'disabled'])->save();
        $this->get($url)->assertRedirect();
    }

    public function test_quiz_answers_are_hidden_and_checked_on_the_server(): void
    {
        Course::ensureTables();
        DB::table('course_lessons')->insert(['slug' => 'quiz-lesson', 'title' => 'Quiz lesson', 'summary' => 's', 'position' => 1, 'status' => 'published', 'created_at' => now(), 'updated_at' => now(),
            'body' => '<h2>Check your understanding</h2><details><summary><strong>1. What is 2 + 2?</strong></summary><p>Secret model answer: four.</p></details>']);
        config(['chatbot.api_key' => 'test-key']);
        \Illuminate\Support\Facades\Http::fake(['api.anthropic.com/*' => \Illuminate\Support\Facades\Http::response(['content' => [['type' => 'text', 'text' => '{"verdict":"correct","feedback":"Yes, the sum is right."}']]])]);

        $this->postJson('/courses/check-answer', ['lesson' => 'quiz-lesson', 'q' => 0, 'answer' => 'four'])->assertForbidden();

        $this->actingAs($this->member(), 'member');
        $this->get(Course::PATH.'/quiz-lesson')->assertOk()->assertSee('What is 2 + 2?')->assertDontSee('Secret model answer')->assertSee('data-q="0"', false);
        $this->postJson('/courses/check-answer', ['lesson' => 'quiz-lesson', 'q' => 0, 'answer' => 'four'])->assertOk()->assertJson(['verdict' => 'correct', 'feedback' => 'Yes, the sum is right.']);
        $this->postJson('/courses/check-answer', ['lesson' => 'quiz-lesson', 'q' => 5, 'answer' => 'x'])->assertNotFound();
        $this->postJson('/courses/check-answer', ['lesson' => 'quiz-lesson', 'q' => 0, 'answer' => ' '])->assertStatus(422);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains(json_encode($r->data()), 'Secret model answer'));
    }

    public function test_answer_checks_are_limited_per_member_not_per_internet_address(): void
    {
        Course::ensureTables();
        DB::table('course_lessons')->insert(['slug' => 'quiz-lesson', 'title' => 'Quiz lesson', 'summary' => 's', 'position' => 1, 'status' => 'published', 'created_at' => now(), 'updated_at' => now(),
            'body' => '<details><summary>1. Q?</summary><p>A.</p></details>']);
        config(['chatbot.api_key' => 'test-key']);
        \Illuminate\Support\Facades\Http::fake(['api.anthropic.com/*' => \Illuminate\Support\Facades\Http::response(['content' => [['type' => 'text', 'text' => '{"verdict":"correct","feedback":"ok"}']]])]);
        $a = $this->member('ana@example.com');
        $b = $this->member('ben@example.com');
        $this->actingAs($a, 'member');
        for ($n = 0; $n < 20; $n++) {
            $this->postJson('/courses/check-answer', ['lesson' => 'quiz-lesson', 'q' => 0, 'answer' => 'A'])->assertOk();
        }
        $this->postJson('/courses/check-answer', ['lesson' => 'quiz-lesson', 'q' => 0, 'answer' => 'A'])->assertStatus(429);
        // Same internet address, another student: not blocked.
        $this->actingAs($b, 'member');
        session()->forget('member_pw');   // a fresh sign-in for the second student
        $this->postJson('/courses/check-answer', ['lesson' => 'quiz-lesson', 'q' => 0, 'answer' => 'A'])->assertOk();
        $this->get(Course::PATH.'/quiz-lesson')->assertSee('The checker is busy, trying again')->assertSee('Your session has expired');
    }

    public function test_lesson_result_shows_passed_or_failed(): void
    {
        Course::ensureTables();
        $body = '<h2>Check your understanding</h2>';
        foreach ([1, 2] as $n) {
            $body .= '<details><summary><strong>'.$n.'. Question '.$n.'?</strong></summary><p>Answer '.$n.'.</p></details>';
        }
        DB::table('course_lessons')->insert(['slug' => 'result-lesson', 'title' => 'Result lesson', 'summary' => 's', 'position' => 1, 'status' => 'published', 'created_at' => now(), 'updated_at' => now(), 'body' => $body]);
        config(['chatbot.api_key' => 'test-key']);
        $reply = fn (string $v) => \Illuminate\Support\Facades\Http::response(['content' => [['type' => 'text', 'text' => '{"verdict":"'.$v.'","feedback":"ok"}']]]);
        \Illuminate\Support\Facades\Http::fake(['api.anthropic.com/*' => \Illuminate\Support\Facades\Http::sequence()->pushResponse($reply('correct'))->pushResponse($reply('wrong'))->pushResponse($reply('correct'))]);

        $student = $this->member();
        $this->actingAs($student, 'member');
        $this->get('/account')->assertSee('No lessons done yet')->assertSee('/account/orders', false)->assertSee('/account/course', false)->assertSee('Online Course')->assertSee('href="/forum"', false)->assertSee('Your Quotations');
        $this->get('/account/orders')->assertOk()->assertSee('Your Orders')->assertSee('No orders yet');
        $this->get('/account/course')->assertOk()->assertSee('No lessons done yet');
        $this->get(Course::PATH.'/result-lesson')->assertSee('Answer and check all 2 questions');
        $first = $this->postJson('/courses/check-answer', ['lesson' => 'result-lesson', 'q' => 0, 'answer' => 'a'])->assertOk()->json('summary_html');
        $this->assertStringContainsString('checked 1 of 2', $first);
        $failed = $this->postJson('/courses/check-answer', ['lesson' => 'result-lesson', 'q' => 1, 'answer' => 'b'])->assertOk()->json('summary_html');
        $this->assertStringContainsString('FAILED', $failed);
        $this->assertStringContainsString('1 of 2', $failed);
        $this->get(Course::PATH)->assertSee('Failed · 50%')->assertSee('0 of 1 lessons passed');

        // A better answer replaces the old one.
        $passed = $this->postJson('/courses/check-answer', ['lesson' => 'result-lesson', 'q' => 1, 'answer' => 'better'])->assertOk()->json('summary_html');
        $this->assertStringContainsString('PASSED', $passed);
        $this->get(Course::PATH.'/result-lesson')->assertSee('PASSED')->assertSee('2 of 2');
        $this->get(Course::PATH)->assertSee('Passed · 100%')->assertSee('1 of 1 lessons passed')->assertSee('passed every lesson');

        // The record on the member's account page, and with the answers for staff.
        $this->get('/account/course')->assertSee('Your course record: 1 of 1 lessons passed')->assertSee('Result lesson');
        $this->get('/account')->assertSee('Your course record: 1 of 1 lessons passed')->assertSee('Result lesson')->assertSee('2 of 2 (100%)')->assertDontSee('better');
        $this->actingAs($this->owner(), 'web')->get('/admin/members/'.$student->id)->assertOk()->assertSee('Online course record')->assertSee('Question 2?')->assertSee('better');
        $this->get('/admin/members')->assertOk()->assertSee('Online course:')->assertSee('1 of 1 lessons passed')->assertSee('last opened Lesson 1: Result lesson')->assertSee('1 lesson opened');
        $this->get('/admin/members/'.$student->id)->assertSee('first on');
    }

    public function test_member_is_led_to_the_next_lesson(): void
    {
        Course::ensureTables();
        foreach ([1, 2, 3] as $n) {
            DB::table('course_lessons')->insert(['slug' => 'step-'.$n, 'title' => 'Step lesson '.$n, 'summary' => 's', 'position' => $n, 'status' => 'published', 'created_at' => now(), 'updated_at' => now(),
                'body' => '<h2>Check your understanding</h2><details><summary><strong>1. Q'.$n.'?</strong></summary><p>A.</p></details>']);
        }
        config(['chatbot.api_key' => 'test-key']);
        \Illuminate\Support\Facades\Http::fake(['api.anthropic.com/*' => \Illuminate\Support\Facades\Http::response(['content' => [['type' => 'text', 'text' => '{"verdict":"correct","feedback":"ok"}']]])]);
        $this->actingAs($this->member(), 'member');

        $this->get('/account/course')->assertSee('Start Lesson 1')->assertSee(Course::PATH.'/step-1', false);
        $this->get('/account')->assertSee('Start the Course')->assertSee('No lessons done yet')->assertDontSee('Start Lesson 1');
        $this->get(Course::PATH.'/step-1')->assertOk();
        $this->get('/account/course')->assertSee('Continue where you left off')->assertSee('Continue Lesson 1');
        $this->get('/account')->assertDontSee('Continue where you left off')->assertDontSee('Start the Course')->assertDontSee('No lessons done yet')->assertSee('Open the course');
        // The record keeps every lesson opened, even before any answer is checked.
        $this->get('/account/course')->assertSee('No lessons passed yet')->assertDontSee('Not checked yet');   // members see only passed lessons
        $this->get(Course::PATH.'/step-1')->assertOk();

        // Lesson 1 finished: next time, straight to Lesson 2.
        $this->postJson('/courses/check-answer', ['lesson' => 'step-1', 'q' => 0, 'answer' => 'a'])->assertOk();
        $this->get('/account/course')->assertSee('You finished Lesson 1: Step lesson 1')->assertSee('Start Lesson 2')->assertSee(Course::PATH.'/step-2', false);
        $this->get(Course::PATH)->assertSee('Start Lesson 2');
        $this->get('/account/course')->assertSee('Start Lesson 2');

        // Opened Lesson 2 but not finished: continue Lesson 2.
        $this->travel(2)->seconds();
        $this->get(Course::PATH.'/step-2')->assertOk();
        $this->get('/account/course')->assertSee('Continue Lesson 2');

        foreach (['step-2', 'step-3'] as $slug) {
            $this->travel(2)->seconds();
            $this->postJson('/courses/check-answer', ['lesson' => $slug, 'q' => 0, 'answer' => 'a'])->assertOk();
        }
        $this->get('/account')->assertSee('You have passed every lesson');

        // The course-wide numbers on Admin → Online course.
        $o = \App\Support\CourseResults::overview();
        $this->assertSame([1, 1, 1, 3, 1], [$o['started'], $o['startedWeek'], $o['passedAny'], $o['maxPassed'], $o['passedAll']]);
        $this->assertSame(1, $o['bands']['All 3 lessons']);
        $this->assertSame([1, 1], [$o['lessons'][1]['opened'], $o['lessons'][1]['passed']]);
        $this->assertSame([3, 1], [$o['timing']['lessonCount'], $o['timing']['courseCount']]);
        $this->assertNotNull($o['timing']['courseAvg']);
        $this->assertGreaterThanOrEqual($o['timing']['lessonMedian'], $o['timing']['courseAvg']);
        $this->actingAs(\App\Models\User::query()->where('role', 'owner')->first() ?? $this->owner(), 'web')->get('/admin/course')->assertSee('How long students take')
            ->assertSee('Average time to finish a lesson')->assertSee('from 3 passed lessons')->assertSee('from 1 student who passed every lesson')->assertSee('Average time to pass')
            ->assertSee('Finished in')->assertSee(now('Asia/Manila')->format('D j M Y'));
        $this->assertNotNull($o['ranking'][0]['started']);
        $this->assertSame($o['timing']['courseAvg'], $o['ranking'][0]['took']);
        $this->actingAs(\App\Models\User::query()->where('role', 'owner')->first() ?? $this->owner(), 'web')->get('/admin/course')->assertOk()->assertSee('Who is taking the course')->assertSee('Started the course')
            ->assertSee('All 3 lessons opened')->assertSee('best so far: 3 of 3 lessons')->assertSee('2. Step lesson 2')
            ->assertSee('Members leading the course')->assertSee('Maria Santos')->assertSee('maria@example.com');
        $this->assertSame(['Maria Santos', 3, 3, 2], [$o['ranking'][0]['name'], $o['ranking'][0]['passed'], $o['ranking'][0]['opened'], $o['ranking'][0]['last']]);

        // Hiding a member (e.g. the owner's own test account) leaves them out of the numbers and the ranking.
        $id = $o['ranking'][0]['id'];
        $this->post('/admin/course/hide/'.$id, ['hide' => '1'])->assertRedirect();
        $o2 = \App\Support\CourseResults::overview();
        $this->assertSame([0, []], [$o2['started'], $o2['ranking']]);
        $this->assertSame(0, \App\Support\CourseResults::studentCount());
        $this->get('/admin/course')->assertSee('Hidden from the course progress and from "Online now" (1)', false)->assertSee('Show again');
        $this->post('/admin/course/hide/'.$id, ['hide' => '0']);
        $this->assertSame(1, \App\Support\CourseResults::overview()['started']);
    }

    public function test_durations_read_in_minutes_hours_days_weeks_and_months(): void
    {
        $d = fn ($x) => \App\Support\CourseResults::duration($x);
        $this->assertSame(['—', 'under 1 min', '45 min', '1 hour', '2 hours 10 min', '3 days 4 hours', '2 weeks 5 hours', '1 week 1 day', '1 month 2 weeks', '2 months 3 days'],
            [$d(null), $d(30), $d(45 * 60), $d(3600 + 59), $d(2 * 3600 + 600), $d(3 * 86400 + 4 * 3600 + 120), $d(14 * 86400 + 5 * 3600), $d(8 * 86400), $d(44 * 86400), $d(63 * 86400)]);
    }

    public function test_gcash_donation_box_before_the_lesson(): void
    {
        $lesson = $this->lesson();
        $owner = $this->owner();
        $this->actingAs($owner, 'web');
        $img = imagecreatetruecolor(300, 300);
        $tmp = tempnam(sys_get_temp_dir(), 'qr').'.png';
        imagepng($img, $tmp);
        $this->post('/admin/course/gcash', ['number' => '0917 123 4567', 'name' => 'J. Legaspi', 'qr' => new UploadedFile($tmp, 'qr.png', 'image/png', null, true)])->assertRedirect();
        auth('web')->logout();
        $this->get(Course::PATH)->assertSee('This course is free. Support it with GCash')->assertSee('0917 123 4567')->assertSee('/courses/gcash-qr.jpg', false);
        $this->get('/courses/gcash-qr.jpg')->assertOk();
        $this->actingAs($this->member(), 'member');
        $page = $this->get(Course::PATH.'/'.$lesson->slug)->assertOk()->getContent();
        $this->assertLessThan(strpos($page, 'Secret notes'), strpos($page, 'Support it with GCash'));   // donation box comes before the lesson

        // The member reports the donation: the box disappears from the lessons; staff can reject it.
        $this->post('/courses/donated', ['reference' => '12'])->assertSessionHas('status_error');
        $this->post('/courses/donated', ['reference' => '1234 567 890123', 'amount' => '200'])->assertSessionHas('account_status');
        $this->get(Course::PATH.'/'.$lesson->slug)->assertOk()->assertDontSee('Support it with GCash')->assertSee('Secret notes');
        $this->get(Course::PATH)->assertSee('Thank you for your donation');
        $d = DB::table('course_donations')->first();
        $this->assertSame('1234567890123', $d->reference);
        auth('member')->logout();
        $this->actingAs($owner, 'web');
        $this->get('/admin/course')->assertSee('1234567890123')->assertSee('₱200.00');
        $this->post('/admin/course/donations/'.$d->id, ['status' => 'rejected'])->assertRedirect();
        auth('web')->logout();
        $this->actingAs(\App\Models\Member::find($d->member_id), 'member');
        $this->get(Course::PATH.'/'.$lesson->slug)->assertSee('Support it with GCash');
        @unlink(Course::gcashQrFile());
    }

    public function test_tagalog_version_of_a_lesson(): void
    {
        $lesson = $this->lesson();
        $m = $this->member();
        $this->actingAs($m, 'member');
        Course::extend($m->id);
        $url = Course::PATH.'/'.$lesson->slug;
        $this->get($url.'/tagalog')->assertRedirect($url);                 // no Tagalog yet
        $this->get($url)->assertDontSee('Basahin ang araling ito sa Tagalog');
        DB::table('course_lessons')->where('id', $lesson->id)->update(['title_tl' => 'Pag-unawa sa Enerhiya ng Araw', 'body_tl' => '<p>Lihim na tala</p>[[slide:1]]']);
        $this->get($url)->assertSee('Basahin ang araling ito sa Tagalog');
        $this->get($url.'/tagalog')->assertOk()->assertSee('Pag-unawa sa Enerhiya ng Araw')->assertSee('Lihim na tala')->assertSee('/courses/slide/', false)->assertSee('Read this lesson in English');
        // Lessons always open in English, even for visitors who chose Filipino on the site.
        $this->withCookie('lang', 'fil')->get($url)->assertOk()->assertSee('Secret notes')->assertSee('href="'.$url.'/tagalog"', false);
        auth('member')->logout();
        $this->get($url.'/tagalog')->assertRedirect(Course::PATH);       // still paid members only
    }

    public function test_staff_can_preview_drafts(): void
    {
        $lesson = $this->lesson('draft');
        $this->get(Course::PATH)->assertDontSee('Understanding Solar Energy');
        $this->actingAs($this->owner(), 'web');
        $this->get(Course::PATH.'/'.$lesson->slug)->assertOk()->assertSee('Staff preview')->assertSee('Secret notes');
    }

    public function test_online_payment_unlocks_one_year(): void
    {
        $this->lesson();
        Course::setPrice(4999);
        $m = $this->member();
        $this->actingAs($m, 'member');
        Http::fake(['api.paymongo.com/*' => Http::response(['data' => ['id' => 'cs_mem_1', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_mem_1']]], 200)]);
        $this->post('/courses/join')->assertRedirect('https://checkout.paymongo.com/cs_mem_1');
        $ref = DB::table('membership_payments')->value('reference');
        $this->assertMatchesRegularExpression('/^MEM-\d{6}-[A-Z0-9]{4}$/', $ref);
        Http::assertSent(fn ($r) => $r['data']['attributes']['line_items'][0]['amount'] === 499900 && $r['data']['attributes']['reference_number'] === $ref);

        $this->webhook($ref, 100000)->assertOk();             // too little: not unlocked
        $this->assertFalse(Course::isPaid($m->fresh()));
        $this->webhook($ref, 499900)->assertOk();
        $this->webhook($ref, 499900)->assertOk();             // PayMongo retries: still one year
        $until = Course::paidUntil($m->fresh());
        $this->assertTrue($until->between(now()->addMonths(12)->subDay(), now()->addMonths(12)->addDay()));
        $this->assertSame('paid', DB::table('membership_payments')->value('status'));
    }

    public function test_admin_manages_price_lessons_and_members(): void
    {
        $this->actingAs($this->owner(), 'web');
        $this->get('/admin/course')->assertOk()->assertSee('GCash donations');
        $this->post('/admin/course/price', ['price' => '₱5,500'])->assertRedirect();
        $this->assertSame(5500.0, Course::price());
        $this->post('/admin/course/lessons', ['title' => 'Understanding Solar Energy'])->assertRedirect();
        $id = DB::table('course_lessons')->value('id');
        $this->post('/admin/course/lessons/'.$id, ['title' => 'Understanding Solar Energy', 'slug' => 'understanding-solar-energy', 'summary' => 'Intro', 'body' => '<p onclick="x()">Hi</p>', 'status' => 'published', 'position' => 1])->assertRedirect();
        $this->assertSame('<p>Hi</p>', DB::table('course_lessons')->value('body'));
        $this->member('juan@example.com');
        $this->post('/admin/course/member', ['email' => 'juan@example.com', 'action' => 'give'])->assertRedirect();
        $this->assertTrue(Course::isPaid(Member::query()->where('email', 'juan@example.com')->first()));
        $this->post('/admin/course/member', ['email' => 'juan@example.com', 'action' => 'end']);

        // Banners: course page and a lesson, public image URLs in all widths.
        $img = imagecreatetruecolor(2000, 900);
        $tmp = tempnam(sys_get_temp_dir(), 'bn').'.jpg';
        imagejpeg($img, $tmp);
        $this->post('/admin/course/banner', ['file' => new UploadedFile($tmp, 'banner.jpg', 'image/jpeg', null, true)])->assertRedirect();
        $this->post('/admin/course/banner', ['lesson' => $id, 'focus' => '40% 60%', 'file' => new UploadedFile($tmp, 'banner.jpg', 'image/jpeg', null, true)])->assertRedirect();
        $this->assertTrue(Course::hasBanner('course') && Course::hasBanner('lesson-'.$id));
        auth('web')->logout();
        $this->get(Course::PATH)->assertSee('/courses/banners/course-1280.jpg', false);
        $this->get('/courses/banners/lesson-'.$id.'-640.jpg')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get('/courses/banners/../x-640.jpg')->assertNotFound();
        Course::deleteBanner('course');
        Course::deleteBanner('lesson-'.$id);
        $this->assertFalse(Course::isPaid(Member::query()->where('email', 'juan@example.com')->first()));
    }

    public function test_answer_checks_retry_when_busy_and_read_cut_off_replies(): void
    {
        config(['chatbot.api_key' => 'test-key']);
        Cache::flush();
        $ok = fn (string $text) => \Illuminate\Support\Facades\Http::response(['content' => [['type' => 'text', 'text' => $text]]]);
        \Illuminate\Support\Facades\Http::fake(['api.anthropic.com/*' => \Illuminate\Support\Facades\Http::sequence()
            ->push(['type' => 'error', 'error' => ['type' => 'overloaded_error', 'message' => 'Overloaded']], 529)
            ->push(['error' => ['message' => 'rate limited']], 429)
            ->pushResponse($ok('{"verdict":"correct","feedback":"Yes, that is it."}'))
            ->pushResponse($ok('```json\n{"verdict": "partly", "feedback": "Malapit na, pero kulang pa ang'))   // cut off
            ->push(['error' => ['type' => 'authentication_error', 'message' => 'invalid x-api-key']], 401)]);

        $r = \App\Support\CourseQuiz::check('Q?', 'A', 'my answer', false);
        $this->assertSame('correct', $r['verdict']);   // third try worked

        $r = \App\Support\CourseQuiz::check('Q?', 'A', 'sagot', true);
        $this->assertSame('partly', $r['verdict']);
        $this->assertStringStartsWith('Malapit na', $r['feedback']);

        $r = \App\Support\CourseQuiz::check('Q?', 'A', 'x', false);
        $this->assertArrayHasKey('error', $r);   // wrong key: not retried
        \Illuminate\Support\Facades\Http::assertSentCount(5);

        $st = \App\Support\CourseQuiz::todayStats();
        $this->assertSame([2, 1, 3], [$st['checked'], $st['failed'], $st['retried']]);
        $this->assertStringContainsString('AI replied 401: invalid x-api-key', $st['problem']);
        $owner = \App\Models\User::query()->where('role', 'owner')->first() ?? $this->owner();
        $this->actingAs($owner, 'web')->get('/admin/course')->assertSee('Answer checks today:')->assertSee('2 checked, 1 could not be checked even after 3 tries')->assertSee('3 tries went wrong and worked on the next try');
    }

    public function test_an_unreadable_reply_is_tried_again(): void
    {
        config(['chatbot.api_key' => 'test-key']);
        Cache::flush();
        $ok = fn (string $text) => \Illuminate\Support\Facades\Http::response(['content' => [['type' => 'text', 'text' => $text]]]);
        \Illuminate\Support\Facades\Http::fake(['api.anthropic.com/*' => \Illuminate\Support\Facades\Http::sequence()
            ->pushResponse($ok('I think the student is mostly right.'))
            ->pushResponse($ok("verdict: 'correct'")), ]);
        $r = \App\Support\CourseQuiz::check('Q?', 'A', 'answer', false);
        $this->assertSame('correct', $r['verdict']);
        \Illuminate\Support\Facades\Http::assertSentCount(2);
        $st = \App\Support\CourseQuiz::todayStats();
        $this->assertSame([1, 0, 1], [$st['checked'], $st['failed'], $st['retried']]);
        $this->assertStringContainsString('I think the student is mostly right', $st['problem']);   // a sample of the reply is kept
    }

    public function test_the_verdict_comes_from_the_grade_tool_and_other_words_are_understood(): void
    {
        config(['chatbot.api_key' => 'test-key']);
        Cache::flush();
        $ok = fn (array $content) => \Illuminate\Support\Facades\Http::response(['content' => $content, 'stop_reason' => 'tool_use']);
        \Illuminate\Support\Facades\Http::fake(['api.anthropic.com/*' => \Illuminate\Support\Facades\Http::sequence()
            ->pushResponse($ok([['type' => 'tool_use', 'name' => 'grade', 'input' => ['verdict' => 'partly', 'feedback' => 'Almost there.']]]))
            ->pushResponse($ok([['type' => 'text', 'text' => '{"verdict":"incorrect","feedback":"Not quite."}']]))
            ->pushResponse($ok([['type' => 'text', 'text' => '{"verdict":"Partially correct","feedback":"Halos."}']]))
            ->pushResponse($ok([['type' => 'text', 'text' => '{"verdict":"Correct","feedback":"Yes."}']]))]);

        $this->assertSame(['partly', 'Almost there.'], array_values(\App\Support\CourseQuiz::check('Q?', 'A', 'a', false)));
        $this->assertSame('wrong', \App\Support\CourseQuiz::check('Q?', 'A', 'b', false)['verdict']);
        $this->assertSame('partly', \App\Support\CourseQuiz::check('Q?', 'A', 'c', true)['verdict']);
        $this->assertSame('correct', \App\Support\CourseQuiz::check('Q?', 'A', 'd', false)['verdict']);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => ($r['tool_choice']['name'] ?? '') === 'grade' && ($r['tools'][0]['input_schema']['properties']['verdict']['enum'] ?? []) === ['correct', 'partly', 'wrong']);
        $this->assertSame([4, 0, 0], array_values(array_slice(\App\Support\CourseQuiz::todayStats(), 0, 3)));
    }
}
