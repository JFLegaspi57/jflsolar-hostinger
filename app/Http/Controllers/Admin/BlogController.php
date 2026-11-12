<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Entities;
use App\Admin\FormParser;
use App\Admin\FormState;
use App\Admin\Saver;
use App\Admin\Values;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\MediaAsset;
use App\Support\Blog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin → Blog: write, publish and unpublish posts for /blog. */
class BlogController extends Controller
{
    public function __construct()
    {
        Blog::ensureTables();
    }

    public function index(Request $request): View
    {
        $archived = $request->query('view') === 'archived';
        $posts = BlogPost::query()->where('status', $archived ? '=' : '!=', 'archived')->orderByRaw('published_at is null desc')->orderByDesc('published_at')->orderByDesc('created_at')->get();
        $photos = MediaAsset::query()->whereNotNull('blog_post_id')->where('kind', 'image')->selectRaw('blog_post_id, count(*) as n')->groupBy('blog_post_id')->pluck('n', 'blog_post_id');

        return view('admin.blog.index', ['posts' => $posts, 'photos' => $photos, 'archived' => $archived]);
    }

    public function create(Request $request): View
    {
        return view('admin.blog.edit', ['post' => null, 'form' => new FormState(Values::post(null), $request->session()->get('errors'))]);
    }

    public function edit(Request $request, string $id): View
    {
        $post = Entities::find('blog', $id);
        $post->photos = $post->media()->where('kind', 'image')->get();
        $post->cover = $post->photos->firstWhere('is_cover', true) ?? $post->photos->first();

        return view('admin.blog.edit', ['post' => $post, 'form' => new FormState(Values::post($post), $request->session()->get('errors'))]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return $this->save($request, Entities::find('blog', $id));
    }

    private function save(Request $request, ?BlogPost $record): RedirectResponse
    {
        $p = new FormParser($request);
        $title = $p->text('title', 'Title', 160, required: true, min: 2);
        $row = [
            'title' => $title,
            'slug' => $p->slug('slug', $title),
            'excerpt' => $p->body('excerpt', 'Short summary', 300),
            'body' => $p->text('body', 'Post', 20000, required: true, min: 20) ?? '',
            // Optional wording for the date, e.g. "10–14 November 2026" for an event over several days.
            'date_label' => $p->text('date_label', 'Show the date as', 60) ?: null,
        ];
        // Optional date shown on the post (e.g. to backdate an older story); blank keeps the first-publish date.
        $date = $p->date('published_on', 'Date shown on the post');
        if ($date && ($date < '2000-01-01' || $date > now('Asia/Manila')->addYear()->toDateString())) {
            $p->errors['published_on'] = 'Choose a date between 2000 and next year.';
        }
        if (! $p->ok()) {
            return Saver::invalid($p);
        }
        // First publish sets the date shown on the post (updates skip model events).
        if ($date) {
            $row['published_at'] = \Illuminate\Support\Carbon::parse($date.' 12:00', 'Asia/Manila')->utc();
        } elseif (Saver::intent($request) === 'publish' && $record && ! $record->published_at) {
            $row['published_at'] = now();
        }

        return Saver::save($request, BlogPost::class, 'blog', $record, $row);
    }
}
