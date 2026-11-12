<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use App\Support\Blog;
use App\Support\BlogComments;
use App\Support\Input;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin → Blog → Comments: review comments that were not clearly positive, hide or restore any comment. */
class BlogCommentController extends Controller
{
    private const VIEWS = ['review' => 'review', 'published' => 'published', 'hidden' => 'hidden'];

    public function index(Request $request): View
    {
        Blog::ensureTables();
        $view = self::VIEWS[Input::str($request, 'view')] ?? 'review';
        $comments = BlogComment::query()->with(['post', 'member'])->where('status', $view)->latest('id')->paginate(30)->withQueryString();

        return view('admin.blog.comments', ['comments' => $comments, 'view' => $view, 'waiting' => BlogComments::waitingCount()]);
    }

    public function update(Request $request, int $comment): RedirectResponse
    {
        $c = BlogComment::query()->findOrFail($comment);
        $status = match (Input::str($request, 'action')) {
            'approve', 'restore' => 'published',
            'hide' => 'hidden',
            default => abort(400),
        };
        $c->update(['status' => $status]);
        $view = self::VIEWS[Input::str($request, 'view')] ?? 'review';

        return redirect('/admin/blog/comments'.($view === 'review' ? '' : '?view='.$view))
            ->with('status', $status === 'published' ? 'Comment published.' : 'Comment hidden from the website.');
    }
}
