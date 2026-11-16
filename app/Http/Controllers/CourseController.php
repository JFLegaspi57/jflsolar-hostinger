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
    public function show(Request $request): Response
    {
        $member = Members::current();
        $paidNote = null;
        if ($member && is_string($request->query('paid')) && preg_match('/^MEM-\d{6}-[A-Z0-9]{4}$/', $request->query('paid'))) {
            $paidNote = Course::confirmPending($request->query('paid'), $member->id) ? 'paid' : 'pending';
        }

        // Not linked from the public menus: members reach the course from their account page.
        return response()->view('pages.course.show', [
            'settings' => PublicContent::settings(),
            'member' => $member,
            'paidUntil' => Course::paidUntil($member),
            'canView' => Course::canView(),
            'lessons' => Course::lessons(),
            'price' => Course::price(),
            'canBuy' => Course::canBuy(),
            'paidNote' => $paidNote,
            'cancelled' => $request->boolean('cancelled'),
        ])->header('X-Robots-Tag', 'noindex');
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
                ? (Course::FREE ? 'Your account is not active. Please contact us.' : 'This lesson is for paid members. Join below to unlock the course.')
                : (Course::FREE ? 'Please sign in or create a free account to open the lessons.' : 'Please sign in. Lessons are for paid members.'));
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
            'staffPreview' => $staff !== null && ! $member,
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

    /** A signed-in member tells us they sent a GCash donation: the box disappears from their lessons. */
    public function donated(Request $request): RedirectResponse
    {
        $member = Members::current();
        if (! $member) {
            return redirect(Course::PATH)->with('status_error', 'Please sign in first.');
        }
        $error = Course::reportDonation($member, (string) $request->input('reference'), (string) $request->input('amount'));

        return back()->with($error ? 'status_error' : 'account_status', $error ?? 'Thank you for your donation! It helps us keep making new lessons.');
    }

    public function gcashQr(): Response
    {
        abort_unless(is_file(Course::gcashQrFile()), 404);

        return response()->file(Course::gcashQrFile(), ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'public, max-age=86400']);
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
