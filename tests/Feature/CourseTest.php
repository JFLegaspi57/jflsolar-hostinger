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

    public function test_lessons_are_for_paid_members_only(): void
    {
        $lesson = $this->lesson();
        $slide = DB::table('course_slides')->first();
        $url = Course::PATH.'/'.$lesson->slug;

        // Everyone sees the course page and lesson titles, never the lesson text or slides.
        $this->get(Course::PATH)->assertOk()->assertSee('Paid members only')->assertSee('Understanding Solar Energy')->assertDontSee('Secret notes')->assertSee('Sign in to join');
        $this->get($url)->assertRedirect(Course::PATH);
        $this->get('/courses/slide/'.$slide->id)->assertForbidden();

        // A free member: still locked.
        $m = $this->member();
        $this->actingAs($m, 'member');
        $this->get($url)->assertRedirect(Course::PATH);
        $this->get('/courses/slide/'.$slide->id)->assertForbidden();
        $this->get(Course::PATH)->assertSee('Online enrolment opens soon');

        // Paid member: full lesson, slide with watermark, no script, not cached.
        Course::extend($m->id);
        $res = $this->get($url)->assertOk()->assertSee('Secret notes')->assertSee('/courses/slide/'.$slide->id, false)->assertSee('maria@example.com')->assertDontSee('alert(1)', false);
        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));
        $this->assertStringContainsString('noindex', (string) $res->headers->get('X-Robots-Tag'));
        $this->get('/courses/slide/'.$slide->id)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get('/account')->assertSee('Your paid membership is active until');

        // Expired: locked again.
        Course::setUntil($m->id, now()->subDay());
        $this->get($url)->assertRedirect(Course::PATH);
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
        $this->get(Course::PATH)->assertSee('₱4,999')->assertSee('Join as a paid member');
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
        $this->get('/admin/course')->assertOk()->assertSee('Online enrolment');
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
        $this->assertFalse(Course::isPaid(Member::query()->where('email', 'juan@example.com')->first()));
    }
}
