<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\Member;
use App\Support\Input;
use App\Support\Forum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin → Forum: approve first posts, hide posts or topics, pin and lock topics. */
class ForumController extends Controller
{
    public function index(Request $request): View
    {
        $view = in_array($request->query('view'), ['recent', 'hidden'], true) ? (string) $request->query('view') : 'pending';
        $posts = Forum::hasTables()
            ? ForumPost::query()->with(['topic', 'member'])
                ->when($view === 'pending', fn ($w) => $w->where('status', 'pending')->orderBy('id'))
                ->when($view === 'hidden', fn ($w) => $w->where('status', 'hidden')->latest('id'))
                ->when($view === 'recent', fn ($w) => $w->where('status', 'published')->latest('id'))
                ->paginate(30)->withQueryString()
            : null;

        return view('admin.forum.index', ['posts' => $posts, 'view' => $view, 'pending' => Forum::pendingCount()]);
    }

    public function post(Request $request, int $post): RedirectResponse
    {
        $p = ForumPost::query()->findOrFail($post);
        $action = Input::str($request, 'action');
        $message = match ($action) {
            'approve', 'restore' => 'Post is now visible.',
            'hide' => 'Post hidden from the public.',
            default => null,
        };
        if (! $message) {
            return back();
        }
        $p->forceFill(['status' => $action === 'hide' ? 'hidden' : 'published'])->save();
        if ($action === 'approve' && $p->member_id) {
            // One approved post makes the member trusted: their next posts appear at once.
            Member::query()->whereKey($p->member_id)->update(['forum_trusted' => true]);
            $message = 'Approved. This member’s next posts will appear without review.';
        }
        Forum::refreshTopic($p->topic_id);

        return $this->back($request, $message);
    }

    public function topic(Request $request, int $topic): RedirectResponse
    {
        $t = ForumTopic::query()->findOrFail($topic);
        $changes = match (Input::str($request, 'action')) {
            'pin' => [['pinned' => true], 'Topic pinned to the top.'],
            'unpin' => [['pinned' => false], 'Topic unpinned.'],
            'lock' => [['locked' => true], 'Topic closed to new replies.'],
            'unlock' => [['locked' => false], 'Topic open for replies again.'],
            'hide' => [['status' => 'hidden'], 'Topic hidden from the public.'],
            'restore' => [['status' => 'published'], 'Topic visible again.'],
            default => null,
        };
        if (! $changes) {
            return back();
        }
        $t->forceFill($changes[0])->save();
        if (($changes[0]['status'] ?? null) === 'published') {
            Forum::refreshTopic($t->id);   // stays waiting if its opening post still needs approval
        }

        return $this->back($request, $changes[1]);
    }

    /** Back to the page the button was on: the forum topic, or Admin → Forum. */
    private function back(Request $request, string $message): RedirectResponse
    {
        $to = Input::str($request, 'back');
        $to = preg_match('#^/(forum/[a-z0-9-]{1,120}|admin/forum(\?view=(recent|hidden))?)$#', $to) ? $to : '/admin/forum';

        return redirect($to)->with($to === '/admin/forum' || str_starts_with($to, '/admin') ? 'status' : 'forum_status', $message);
    }
}
