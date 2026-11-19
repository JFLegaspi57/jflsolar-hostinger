<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Admin → Online course: price, lessons and slides, and who has paid membership. */
class CourseController extends Controller
{
    public function index(): View
    {
        Course::ensureTables();

        return view('admin.course.index', [
            'lessons' => Course::lessons(true),
            'stats' => \App\Support\CourseResults::overview(),
            'price' => Course::price(),
            'online' => \App\Support\Shop::onlinePayment(),
            'members' => DB::table('members')->whereNotNull('paid_until')->orderByDesc('paid_until')->limit(200)->get(['id', 'name', 'email', 'paid_until', 'status']),
            'donations' => DB::table('course_donations')->join('members', 'members.id', '=', 'course_donations.member_id')
                ->orderByDesc('course_donations.id')->limit(100)
                ->get(['course_donations.*', 'members.name', 'members.email']),
            'payments' => DB::table('membership_payments')->join('members', 'members.id', '=', 'membership_payments.member_id')
                ->orderByDesc('membership_payments.id')->limit(30)
                ->get(['membership_payments.*', 'members.name', 'members.email']),
        ]);
    }

    /** Leaves a member out of (or puts them back into) the course numbers and the ranking. */
    public function hide(Request $request, int $member): RedirectResponse
    {
        $hide = $request->input('hide') === '1';
        \App\Support\CourseResults::setHidden($member, $hide);

        return redirect('/admin/course#stats-h')->with('status', $hide ? 'Hidden from the course progress.' : 'Shown again in the course progress.');
    }

    public function price(Request $request): RedirectResponse
    {
        $raw = trim(str_replace([',', '₱', ' '], '', (string) $request->input('price')));
        if ($raw === '') {
            Course::setPrice(null);

            return back()->with('status', 'Price removed. Online enrolment is closed until you set one.');
        }
        if (! is_numeric($raw) || (float) $raw < 20 || (float) $raw > 1000000) {
            return back()->with('status_error', 'Enter the price in pesos, e.g. 4999.');
        }
        Course::setPrice((float) $raw);

        return back()->with('status', 'Membership price saved: ₱'.number_format((float) $raw, 2).' for 1 year.');
    }

    /** Confirm a reported donation (checked in the GCash app), or reject it: the box shows again for that member. */
    public function donation(Request $request, int $id): RedirectResponse
    {
        Course::ensureTables();
        $status = in_array($request->input('status'), ['confirmed', 'rejected', 'reported'], true) ? $request->input('status') : 'reported';
        DB::table('course_donations')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);

        return back()->with('status', 'Donation marked as '.$status.'.');
    }

    /** GCash donation details shown on the course page and at the end of each lesson. */
    /** The GCash QR code students scan to pay for their certificate. */
    public function certificateQr(Request $request): RedirectResponse
    {
        if ($request->boolean('remove')) {
            @unlink(\App\Support\CourseCertificates::qrFile());

            return back()->with('status', 'Certificate payment QR removed.');
        }
        $error = \App\Support\CourseCertificates::saveQr($request->file('qr'));

        return $error ? back()->with('status_error', $error) : back()->with('status', 'Certificate payment QR saved.');
    }

    public function gcash(Request $request): RedirectResponse
    {
        if ($request->boolean('remove_qr')) {
            @unlink(Course::gcashQrFile());

            return back()->with('status', 'GCash QR removed.');
        }
        Course::setGcash((string) $request->input('number'), (string) $request->input('name'));
        if ($request->hasFile('qr') && ($error = Course::saveGcashQr($request->file('qr')))) {
            return back()->with('status_error', $error);
        }

        return back()->with('status', 'GCash donation details saved.');
    }

    /** Upload or remove the banner photo of the course page (lesson = 0) or of one lesson. */
    public function banner(Request $request): RedirectResponse
    {
        Course::ensureTables();
        $lesson = (int) $request->input('lesson', 0);
        if ($lesson && ! DB::table('course_lessons')->where('id', $lesson)->exists()) {
            abort(404);
        }
        $key = $lesson ? 'lesson-'.$lesson : 'course';
        if ($request->boolean('remove')) {
            Course::deleteBanner($key);

            return back()->with('status', 'Banner removed.');
        }
        if ($lesson && preg_match('/^\d{1,3}% \d{1,3}%$/', (string) $request->input('focus'))) {
            DB::table('course_lessons')->where('id', $lesson)->update(['banner_focus' => $request->input('focus')]);
        }
        if (! $request->hasFile('file')) {
            return back()->with('status', 'Banner settings saved.');
        }
        $error = Course::saveBanner($key, $request->file('file'));

        return back()->with($error ? 'status_error' : 'status', $error ?? 'Banner saved.');
    }

    public function createLesson(Request $request): RedirectResponse
    {
        $title = trim(mb_substr((string) $request->input('title'), 0, 160));
        if ($title === '') {
            return back()->with('status_error', 'Give the lesson a title.');
        }
        Course::ensureTables();
        $slug = $this->uniqueSlug(Str::slug($title) ?: 'lesson');
        $id = DB::table('course_lessons')->insertGetId(['slug' => $slug, 'title' => $title, 'summary' => '', 'body' => '', 'position' => (int) DB::table('course_lessons')->max('position') + 1, 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);

        return redirect('/admin/course/lessons/'.$id)->with('status', 'Lesson created as a draft.');
    }

    public function editLesson(int $id): View
    {
        Course::ensureTables();
        $lesson = DB::table('course_lessons')->where('id', $id)->first() ?? abort(404);

        return view('admin.course.lesson', ['lesson' => $lesson, 'slides' => Course::slides($id)]);
    }

    public function updateLesson(Request $request, int $id): RedirectResponse
    {
        Course::ensureTables();
        $lesson = DB::table('course_lessons')->where('id', $id)->first() ?? abort(404);
        $title = trim(mb_substr((string) $request->input('title'), 0, 160));
        $slug = Str::slug((string) $request->input('slug')) ?: Str::slug($title);
        if ($title === '' || $slug === '') {
            return back()->with('status_error', 'The lesson needs a title.');
        }
        if (DB::table('course_lessons')->where('slug', $slug)->where('id', '!=', $id)->exists()) {
            return back()->with('status_error', 'Another lesson already uses that web address.');
        }
        DB::table('course_lessons')->where('id', $id)->update([
            'title' => $title,
            'slug' => mb_substr($slug, 0, 80),
            'summary' => mb_substr(trim((string) $request->input('summary')), 0, 400),
            'body' => Course::cleanHtml((string) $request->input('body')),
            'title_tl' => mb_substr(trim((string) $request->input('title_tl')), 0, 160) ?: null,
            'summary_tl' => mb_substr(trim((string) $request->input('summary_tl')), 0, 400) ?: null,
            'body_tl' => trim((string) $request->input('body_tl')) !== '' ? Course::cleanHtml((string) $request->input('body_tl')) : null,
            'position' => (int) $request->input('position', $lesson->position),
            'status' => $request->input('status') === 'published' ? 'published' : 'draft',
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Lesson saved.');
    }

    public function deleteLesson(int $id): RedirectResponse
    {
        Course::ensureTables();
        foreach (Course::slides($id) as $s) {
            Course::deleteSlide($s);
        }
        @rmdir(Course::dir().'/'.$id);
        DB::table('course_lessons')->where('id', $id)->delete();

        return redirect('/admin/course')->with('status', 'Lesson deleted.');
    }

    public function uploadSlides(Request $request, int $id): RedirectResponse
    {
        Course::ensureTables();
        abort_unless(DB::table('course_lessons')->where('id', $id)->exists(), 404);
        $files = (array) $request->file('files', []);
        if (! $files) {
            return back()->with('status_error', 'Choose one or more slide images.');
        }
        $errors = [];
        foreach (array_slice($files, 0, 60) as $f) {
            if ($e = Course::addSlide($id, $f, (string) $request->input('caption', ''))) {
                $errors[] = ($f?->getClientOriginalName() ?? 'file').': '.$e;
            }
        }

        return back()->with($errors ? 'status_error' : 'status', $errors ? implode(' ', $errors) : count($files).' '.(count($files) === 1 ? 'slide' : 'slides').' added.');
    }

    public function slideOp(Request $request, int $id): RedirectResponse
    {
        Course::ensureTables();
        $slide = DB::table('course_slides')->where('id', $id)->first() ?? abort(404);
        $op = (string) $request->input('op');
        if ($op === 'delete') {
            Course::deleteSlide($slide);

            return back()->with('status', 'Slide deleted.');
        }
        if ($op === 'caption') {
            DB::table('course_slides')->where('id', $id)->update(['caption' => mb_substr(trim((string) $request->input('caption')), 0, 300), 'updated_at' => now()]);

            return back()->with('status', 'Caption saved.');
        }
        if (in_array($op, ['up', 'down'], true)) {
            $list = Course::slides($slide->lesson_id)->values();
            $i = $list->search(fn ($s) => $s->id === $slide->id);
            $j = $op === 'up' ? $i - 1 : $i + 1;
            if ($i !== false && isset($list[$j])) {
                $ids = $list->pluck('id')->all();
                [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
                foreach ($ids as $pos => $sid) {
                    DB::table('course_slides')->where('id', $sid)->update(['position' => $pos + 1]);
                }
            }
        }

        return back();
    }

    /** Give, extend or end a paid membership by hand (e.g. paid at the office). */
    public function member(Request $request): RedirectResponse
    {
        Course::ensureTables();
        $email = mb_strtolower(trim((string) $request->input('email')));
        $member = DB::table('members')->whereRaw('lower(email) = ?', [$email])->first();
        if (! $member) {
            return back()->with('status_error', 'No member account with that email. The person must sign up on the website first.');
        }
        $action = (string) $request->input('action');
        if ($action === 'end') {
            Course::setUntil($member->id, now()->subMinute());

            return back()->with('status', 'Paid membership of '.$member->email.' ended.');
        }
        $date = trim((string) $request->input('until'));
        if ($date !== '') {
            try {
                $until = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Manila')->endOfDay();
            } catch (\Throwable) {
                return back()->with('status_error', 'Use a valid date.');
            }
            Course::setUntil($member->id, $until);
        } else {
            $until = Course::extend($member->id);
        }

        return back()->with('status', $member->email.' is a paid member until '.$until->timezone('Asia/Manila')->format('j F Y').'.');
    }

    private function uniqueSlug(string $base): string
    {
        $slug = mb_substr($base, 0, 70);
        $n = 2;
        while (DB::table('course_lessons')->where('slug', $slug)->exists()) {
            $slug = mb_substr($base, 0, 70).'-'.$n++;
        }

        return $slug;
    }
}
