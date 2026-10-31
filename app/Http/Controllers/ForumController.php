<?php

namespace App\Http\Controllers;

use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Support\Input;
use App\Support\Forum;
use App\Support\Members;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/** Public forum: list, topic page, new topic, replies. */
class ForumController extends Controller
{
    public function index(Request $request): View
    {
        $category = array_key_exists(Input::str($request, 'category'), Forum::CATEGORIES) ? Input::str($request, 'category') : null;
        $q = mb_substr(trim(Input::str($request, 'q')), 0, 80);
        $topics = Forum::hasTables()
            ? ForumTopic::query()->where('status', 'published')
                ->when($category, fn ($w) => $w->where('category', $category))
                ->when($q !== '', fn ($w) => $w->where('title', 'like', '%'.addcslashes($q, '%_\\').'%'))
                ->orderByDesc('pinned')->orderByDesc('last_post_at')->paginate(20)->withQueryString()
            : null;

        return view('pages.forum.index', ['topics' => $topics, 'category' => $category, 'q' => $q, 'member' => Members::current(), 'staff' => Forum::staff()]);
    }

    public function show(string $slug): View
    {
        abort_unless(Forum::hasTables(), 404);
        $topic = ForumTopic::query()->where('slug', $slug)->firstOrFail();
        $member = Members::current();
        $staff = Forum::staff();
        $ownPending = $member && $topic->member_id === $member->id;
        // Hidden or waiting topics: only staff, and the author (for a topic waiting for approval).
        if ($topic->status !== 'published' && ! $staff && ! ($topic->status === 'pending' && $ownPending)) {
            abort(404);
        }
        $posts = $topic->posts()->orderBy('id')
            ->when(! $staff, fn ($w) => $w->where(fn ($x) => $x->where('status', 'published')->when($member, fn ($y) => $y->orWhere(fn ($z) => $z->where('status', 'pending')->where('member_id', $member->id)))))
            ->get();

        return view('pages.forum.show', ['topic' => $topic, 'posts' => $posts, 'member' => $member, 'staff' => $staff]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! Members::current() && ! Forum::staff()) {
            return redirect('/login?next=%2Fforum%2Fnew');
        }

        return view('pages.forum.new', ['category' => array_key_exists(Input::str($request, 'category'), Forum::CATEGORIES) ? Input::str($request, 'category') : null]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$member, $staff] = [Members::current(), Forum::staff()];
        if (! $member && ! $staff) {
            return redirect('/login?next=%2Fforum%2Fnew');
        }
        Forum::ensureTables();
        $values = ['title' => trim(Input::str($request, 'title')), 'body' => trim(Input::str($request, 'body')), 'category' => Input::str($request, 'category')];
        $back = fn (array $errors) => redirect('/forum/new')->withInput($values)->withErrors($errors);
        if (Input::str($request, 'website') !== '') {
            return redirect('/forum');
        }
        $errors = [];
        if (! array_key_exists($values['category'], Forum::CATEGORIES)) {
            $errors['category'] = 'Please choose a topic.';
        }
        if (mb_strlen($values['title']) < 8 || mb_strlen($values['title']) > 140) {
            $errors['title'] = 'Please write a title of 8 to 140 characters, e.g. “Which battery for a 6 kW hybrid inverter?”';
        } elseif (preg_match('#https?://#i', $values['title'])) {
            $errors['title'] = 'Please don’t put links in the title.';
        }
        if ($problem = Forum::bodyProblem($values['body'])) {
            $errors['body'] = $problem;
        }
        if ($errors) {
            return $back($errors);
        }
        if ($tooMany = $this->tooMany($member, $staff)) {
            return $back(['body' => $tooMany]);
        }

        $status = $staff ? 'published' : Forum::initialStatus($member);
        $author = $this->author($member, $staff);
        $topic = DB::transaction(function () use ($values, $status, $author, $member, $staff) {
            $topic = ForumTopic::query()->create([
                'slug' => Forum::newSlug($values['title']), 'category' => $values['category'], 'title' => $values['title'],
                'member_id' => $staff ? null : $member?->id, 'staff_user_id' => $staff?->id, 'author_name' => $author,
                'status' => $status === 'published' ? 'published' : 'pending', 'last_post_at' => now(),
            ]);
            $topic->posts()->create(['is_opening' => true, 'member_id' => $staff ? null : $member?->id, 'staff_user_id' => $staff?->id, 'author_name' => $author, 'body' => $values['body'], 'status' => $status]);

            return $topic;
        });

        return redirect($topic->path())->with('forum_status', $status === 'published'
            ? 'Your question is posted.'
            : 'Thanks! Your question will appear after a quick check by our team (first posts only). You can see it here meanwhile.');
    }

    public function reply(Request $request, string $slug): RedirectResponse
    {
        [$member, $staff] = [Members::current(), Forum::staff()];
        if (! $member && ! $staff) {
            return redirect('/login?next='.rawurlencode('/forum/'.$slug));
        }
        abort_unless(Forum::hasTables(), 404);
        $topic = ForumTopic::query()->where('slug', $slug)->firstOrFail();
        $back = redirect($topic->path().'#reply');
        if ($topic->status !== 'published' && ! $staff) {
            abort(404);
        }
        if ($topic->locked && ! $staff) {
            return $back->with('forum_error', 'This topic is closed to new replies.');
        }
        $body = trim(Input::str($request, 'body'));
        if (Input::str($request, 'website') !== '') {
            return $back;
        }
        if ($problem = Forum::bodyProblem($body)) {
            return $back->withInput(['body' => $body])->withErrors(['body' => $problem]);
        }
        if ($tooMany = $this->tooMany($member, $staff)) {
            return $back->withInput(['body' => $body])->withErrors(['body' => $tooMany]);
        }
        $status = $staff ? 'published' : Forum::initialStatus($member);
        $post = $topic->posts()->create(['member_id' => $staff ? null : $member?->id, 'staff_user_id' => $staff?->id, 'author_name' => $this->author($member, $staff), 'body' => $body, 'status' => $status]);
        Forum::refreshTopic($topic->id);

        return redirect($topic->path().'#post-'.$post->id)->with('forum_status', $status === 'published'
            ? 'Your reply is posted.'
            : 'Thanks! Your reply will appear after a quick check by our team (first posts only).');
    }

    private function author($member, $staff): string
    {
        return $staff ? Forum::STAFF_NAME : Forum::displayName($member->name);
    }

    /** Members: at most 5 posts per 10 minutes and 30 per day. */
    private function tooMany($member, $staff): ?string
    {
        if ($staff) {
            return null;
        }
        $short = 'forum-post-10m:'.$member->id;
        $day = 'forum-post-day:'.$member->id;
        if (RateLimiter::tooManyAttempts($short, 5) || RateLimiter::tooManyAttempts($day, 30)) {
            return 'You have posted a lot in a short time. Please wait a few minutes and try again.';
        }
        RateLimiter::hit($short, 600);
        RateLimiter::hit($day, 86400);

        return null;
    }
}
