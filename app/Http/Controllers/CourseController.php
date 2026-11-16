<?php

namespace App\Http\Controllers;

use App\Support\Course;
use App\Support\Forum;
use App\Support\Members;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** The online course: an open course page, lessons for paid members only, and paying online. */
class CourseController extends Controller
{
    public function show(Request $request): \Illuminate\View\View
    {
        $member = Members::current();
        $paidNote = null;
        if ($member && is_string($request->query('paid')) && preg_match('/^MEM-\d{6}-[A-Z0-9]{4}$/', $request->query('paid'))) {
            $paidNote = Course::confirmPending($request->query('paid'), $member->id) ? 'paid' : 'pending';
        }

        return view('pages.course.show', [
            'settings' => PublicContent::settings(),
            'member' => $member,
            'paidUntil' => Course::paidUntil($member),
            'canView' => Course::canView(),
            'lessons' => Course::lessons(),
            'price' => Course::price(),
            'canBuy' => Course::canBuy(),
            'paidNote' => $paidNote,
            'cancelled' => $request->boolean('cancelled'),
        ]);
    }

    public function lessonTagalog(string $slug): Response
    {
        return $this->lesson($slug, true);
    }

    public function lesson(string $slug, bool $tagalog = false): Response
    {
        $staff = Forum::staff();
        $lesson = Course::lesson($slug, $staff !== null) ?? abort(404);
        if ($tagalog && ! Course::hasTagalog($lesson)) {
            return redirect(Course::PATH.'/'.$lesson->slug);
        }
        if (! Course::canView()) {
            return redirect(Course::PATH)->with('status_error', Members::current()
                ? 'This lesson is for paid members. Join below to unlock the course.'
                : 'Please sign in. Lessons are for paid members.');
        }
        $member = Members::current();
        $slides = Course::slides($lesson->id);
        $all = Course::lessons($staff !== null)->values();
        $i = $all->search(fn ($l) => $l->id === $lesson->id);

        return response()->view('pages.course.lesson', [
            'settings' => PublicContent::settings(),
            'lesson' => $lesson,
            'slides' => $slides,
            'body' => Course::renderBody($lesson, $slides, $member?->email ?? ($staff?->email ?? ''), $tagalog),
            'tl' => $tagalog,
            'prev' => $i !== false && $i > 0 ? $all[$i - 1] : null,
            'next' => $i !== false ? ($all[$i + 1] ?? null) : null,
            'position' => $i === false ? null : $i + 1,
            'total' => $all->count(),
            'staffPreview' => $staff !== null && ! Course::isPaid($member),
        ])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** Slide images: only for paid members and staff, never cached by shared caches. */
    public function slide(int $id): Response
    {
        abort_unless(Course::canView(), 403);
        Course::ensureTables();
        $slide = DB::table('course_slides')->where('id', $id)->first() ?? abort(404);
        $path = Course::slidePath($slide);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Content-Disposition' => 'inline',
        ]);
    }

    /** Banner photos are public (the course page shows them to everyone). */
    public function banner(string $file): Response
    {
        abort_unless(preg_match('/^(course|lesson-\d+)-(\d+)\.jpg$/', $file, $m) && in_array((int) $m[2], Course::BANNER_WIDTHS, true), 404);
        $path = Course::bannerFile($m[1], (int) $m[2]);
        abort_unless(is_file($path), 404);

        return response()->file($path, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'public, max-age=2592000']);
    }

    public function join(): RedirectResponse
    {
        $member = Members::current();
        if (! $member) {
            return redirect('/login?next='.rawurlencode(Course::PATH));
        }
        if (! Course::canBuy()) {
            return redirect(Course::PATH)->with('status_error', 'Online enrolment is not open yet. Please call our Sales team.');
        }
        try {
            $checkout = Course::startCheckout($member);
        } catch (\Throwable $e) {
            Log::error('[course] checkout failed: '.$e->getMessage());

            return redirect(Course::PATH)->with('status_error', 'We could not open the payment page just now. Please try again in a few minutes, or call our Sales team.');
        }

        return redirect()->away($checkout['url']);
    }
}
