<?php

namespace App\Http\Controllers\Admin;

use App\Admin\FormParser;
use App\Admin\FormState;
use App\Admin\Saver;
use App\Admin\Values;
use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\MediaAsset;
use App\Support\MediaStore;
use App\Support\PublicContent;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    private const SECTIONS = ['about', 'why', 'home', 'services', 'legal'];

    public function index(): View
    {
        return view('admin.content.index', ['blocks' => ContentBlock::query()->orderBy('section')->orderBy('sort_order')->get()]);
    }

    public function create(Request $request): View
    {
        return view('admin.content.edit', ['block' => null, 'form' => new FormState(Values::block(null), $request->session()->get('errors'))]);
    }

    public function edit(Request $request, string $key): View
    {
        $block = ContentBlock::query()->find($key) ?? abort(404);

        return view('admin.content.edit', [
            'block' => $block,
            'images' => $block->media()->where('kind', 'image')->get(),
            'form' => new FormState(Values::block($block), $request->session()->get('errors')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        return $this->save($request, ContentBlock::query()->find($key) ?? abort(404));
    }

    private function save(Request $request, ?ContentBlock $block): RedirectResponse
    {
        $p = new FormParser($request);
        // Existing blocks keep their key and section (they decide placement).
        $section = $block ? $block->section : $p->choice('section', 'Section', ['about', 'why'], required: true);
        $title = $p->text('title', 'Title', 160, required: true);
        $key = $block?->key;
        if (! $key) {
            $key = $p->text('key', 'Key', 60) ?: ($section && $title ? $section.'.'.substr(str_replace('-', '_', FormParser::slugify($title)), 0, 40) : null);
            if ($key && ! preg_match('/^[a-z0-9]+([._-][a-z0-9]+)*$/', $key)) {
                $p->errors['key'] = 'Use lowercase letters and numbers, separated by dots or hyphens (e.g. about.team).';
            }
        }
        $row = [
            'section' => $section,
            'title' => $title,
            'subtitle' => $p->text('subtitle', 'Subtitle', 200),
            'body' => $p->body('body', 'Text', 6000),
            'claim_status' => $p->bool('confirmed') ? 'confirmed' : 'needs_confirmation',
            'review_note' => $p->text('review_note', 'Review note', 1000),
            'published' => $p->bool('published'),
            'sort_order' => $p->int('sort_order', 'Order', -100000, 100000) ?? 0,
        ];
        if (! $p->ok()) {
            return Saver::invalid($p);
        }

        try {
            if (! $block) {
                if (ContentBlock::query()->whereKey($key)->exists()) {
                    return Saver::failed('A block with this key already exists.', ['key' => 'Already used. Choose a different key.']);
                }
                ContentBlock::query()->create(['key' => $key] + $row);
                PublicContent::flush();

                return redirect('/admin/content/'.rawurlencode($key))->with('status', 'Block created.');
            }
            $version = (int) $request->input('lock_version', -1);
            $updated = ContentBlock::query()->whereKey($block->key)->where('lock_version', $version)
                ->update($row + ['lock_version' => $version + 1, 'updated_at' => now()]);
            if (! $updated) {
                return Saver::failed('Someone else saved this block after you opened it. Reload the page and apply your changes again.');
            }
        } catch (QueryException $e) {
            return Saver::failed(...Saver::dbError($e));
        }
        PublicContent::flush();

        return redirect('/admin/content/'.rawurlencode($block->key))->with('status', 'Saved.');
    }

    public function destroy(string $key): RedirectResponse
    {
        $block = ContentBlock::query()->find($key) ?? abort(404);
        $media = MediaAsset::query()->where('content_block_key', $key)->get();
        $block->delete(); // media rows cascade
        foreach ($media as $m) {
            MediaStore::deleteFiles($m);
        }
        PublicContent::flush();

        return redirect('/admin/content')->with('status', 'Block deleted.');
    }
}
