<?php

namespace App\Http\Controllers;

use App\Models\BlogComment;
use App\Models\BlogReaction;
use App\Support\Blog;
use App\Support\BlogComments;
use App\Support\Forum;
use App\Support\Input;
use App\Support\Members;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/** Members react to and comment on blog posts. */
class BlogCommentController extends Controller
{
    public function react(Request $request, string $slug): RedirectResponse
    {
        $post = Blog::find($slug) ?? abort(404);
        $back = '/blog/'.$post->slug.'#comments';
        $member = Members::current();
        if (! $member) {
            return redirect('/login?next='.rawurlencode('/blog/'.$post->slug));
        }
        $reaction = Input::str($request, 'reaction');
        abort_unless(array_key_exists($reaction, BlogComments::REACTIONS), 400);

        $existing = BlogReaction::query()->where('blog_post_id', $post->id)->where('member_id', $member->id)->first();
        if ($existing && $existing->reaction === $reaction) {
            $existing->delete(); // tapping your current reaction again removes it
        } elseif ($existing) {
            $existing->update(['reaction' => $reaction]);
        } else {
            BlogReaction::query()->create(['blog_post_id' => $post->id, 'member_id' => $member->id, 'reaction' => $reaction]);
        }

        return redirect($back);
    }

    public function comment(Request $request, string $slug): RedirectResponse
    {
        $post = Blog::find($slug) ?? abort(404);
        $back = '/blog/'.$post->slug.'#comments';
        $member = Members::current();
        if (! $member) {
            return redirect('/login?next='.rawurlencode('/blog/'.$post->slug));
        }
        if (Input::str($request, 'website') !== '') { // spam trap
            return redirect($back);
        }
        $body = trim(Input::str($request, 'body'));
        if ($problem = BlogComments::problem($body)) {
            return redirect($back)->withInput(['body' => $body])->withErrors(['body' => $problem]);
        }
        $key = 'blog-comment|'.$member->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return redirect($back)->withInput(['body' => $body])->withErrors(['body' => 'You’ve posted several comments in a short time. Please try again in a few minutes.']);
        }
        RateLimiter::hit($key, 600);

        [$publish, $note] = BlogComments::judge($body);
        BlogComment::query()->create([
            'blog_post_id' => $post->id, 'member_id' => $member->id, 'author_name' => Forum::displayName($member->name),
            'body' => $body, 'status' => $publish ? 'published' : 'review', 'check_note' => mb_substr($note, 0, 120),
        ]);

        return redirect($back)->with('comment_status', $publish
            ? 'Thank you! Your comment is posted.'
            : 'Thank you! Your comment will appear once our team has reviewed it.');
    }
}
