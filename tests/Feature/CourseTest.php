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
        $res = $this->get($url)->assertOk()->assertSee('Secret notes')->assertSee('/courses/slide/'.$slide->id, false)->assertSee('maria@example.com')->assertDontSee('alert(1)', false);
        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));
        $this->assertStringContainsString('noindex', (string) $res->headers->get('X-Robots-Tag'));
        $this->get('/courses/slide/'.$slide->id)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get('/account')->assertSee('Solar PV System Designing');

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
}
