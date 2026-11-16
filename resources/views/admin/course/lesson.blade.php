@php
    use App\Support\Course;
    $btn = 'inline-flex min-h-11 items-center rounded-[var(--radius-control)] px-4 font-semibold';
    $box = 'mb-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4 sm:p-6';
    $field = 'w-full min-h-11 rounded-[var(--radius-control)] border border-steel-500 px-3 font-normal';
@endphp
<x-admin.layout :title="'Lesson: '.$lesson->title">
    <x-admin.page-title :title="$lesson->title" back="/admin/course" back-label="Online course" :intro="($lesson->status === 'published' ? 'Published' : 'Draft').' · '.Course::PATH.'/'.$lesson->slug">
        <x-slot:action><a href="{{ Course::PATH }}/{{ $lesson->slug }}" target="_blank" rel="noopener" class="{{ $btn }} border border-steel-400 text-steel-900">Preview</a></x-slot:action>
    </x-admin.page-title>

    <form method="post" action="/admin/course/lessons/{{ $lesson->id }}" class="{{ $box }} grid gap-4 sm:grid-cols-2">
        @csrf
        <label class="grid gap-1 font-semibold text-steel-900 sm:col-span-2">Title <input name="title" value="{{ $lesson->title }}" maxlength="160" required class="{{ $field }}"></label>
        <label class="grid gap-1 font-semibold text-steel-900">Web address <input name="slug" value="{{ $lesson->slug }}" maxlength="80" class="{{ $field }}"></label>
        <label class="grid gap-1 font-semibold text-steel-900">Order <input name="position" type="number" value="{{ $lesson->position }}" class="{{ $field }}"></label>
        <label class="grid gap-1 font-semibold text-steel-900 sm:col-span-2">Short summary <span class="text-sm font-normal text-steel-600">Shown to everyone in the lesson list.</span>
            <textarea name="summary" rows="2" maxlength="400" class="{{ $field }} py-2">{{ $lesson->summary }}</textarea></label>
        <label class="grid gap-1 font-semibold text-steel-900 sm:col-span-2">Lesson text (paid members only)
            <span class="text-sm font-normal text-steel-600">HTML: &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;table&gt;, &lt;div class="calc"&gt; for calculations, &lt;div class="key"&gt; for key points. Put a slide where you want it with [[slide:1]], [[slide:2]] … (numbers as listed below). Without any [[slide:…]], all slides show after the text.</span>
            <textarea name="body" rows="22" class="{{ $field }} py-2 font-mono text-sm">{{ $lesson->body }}</textarea></label>
        <details class="sm:col-span-2 rounded-[var(--radius-control)] border border-steel-200 p-3" @if ($lesson->body_tl) open @endif>
            <summary class="cursor-pointer font-semibold text-steel-900">Tagalog version {{ $lesson->body_tl ? '(added)' : '(none yet)' }}</summary>
            <div class="mt-3 grid gap-4">
                <label class="grid gap-1 font-semibold text-steel-900">Title (Tagalog) <input name="title_tl" value="{{ $lesson->title_tl }}" maxlength="160" class="{{ $field }}"></label>
                <label class="grid gap-1 font-semibold text-steel-900">Short summary (Tagalog) <textarea name="summary_tl" rows="2" maxlength="400" class="{{ $field }} py-2">{{ $lesson->summary_tl }}</textarea></label>
                <label class="grid gap-1 font-semibold text-steel-900">Lesson text (Tagalog) <span class="text-sm font-normal text-steel-600">Same format and [[slide:N]] numbers as the English text. Leave empty for no Tagalog version.</span>
                    <textarea name="body_tl" rows="16" class="{{ $field }} py-2 font-mono text-sm">{{ $lesson->body_tl }}</textarea></label>
            </div>
        </details>
        <label class="grid gap-1 font-semibold text-steel-900">Status
            <select name="status" class="{{ $field }}"><option value="draft" @selected($lesson->status !== 'published')>Draft (staff only)</option><option value="published" @selected($lesson->status === 'published')>Published (paid members)</option></select></label>
        <div class="flex items-end"><button type="submit" class="{{ $btn }} bg-steel-900 text-white">Save lesson</button></div>
    </form>

    <section class="{{ $box }}" aria-labelledby="lbanner-h">
        <h2 id="lbanner-h" class="text-lg font-bold text-steel-950">Lesson banner</h2>
        @php $lb = Course::banner('lesson-'.$lesson->id); @endphp
        @if ($lb)<img src="{{ $lb['src'] }}" alt="" class="mt-3 w-full max-w-xl rounded-[var(--radius-control)] border border-steel-200">@else<p class="mt-1 text-sm text-steel-700">No banner of its own: the course banner is used.</p>@endif
        <form method="post" action="/admin/course/banner" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <input type="hidden" name="lesson" value="{{ $lesson->id }}">
            <label class="grid gap-1 font-semibold text-steel-900">Photo (landscape) <input type="file" name="file" accept="image/jpeg,image/png,image/webp" class="font-normal"></label>
            <label class="grid gap-1 font-semibold text-steel-900">Focus point <input name="focus" value="{{ $lesson->banner_focus ?? '50% 50%' }}" placeholder="50% 50%" class="min-h-11 rounded-[var(--radius-control)] border border-steel-500 px-3 font-normal" style="width: 8rem"></label>
            <button type="submit" class="{{ $btn }} bg-steel-900 text-white">Save banner</button>
        </form>
        @if ($lb)<form method="post" action="/admin/course/banner" class="mt-2">@csrf<input type="hidden" name="lesson" value="{{ $lesson->id }}"><input type="hidden" name="remove" value="1"><button class="text-sm text-danger-700 underline">Remove banner</button></form>@endif
    </section>

    <section class="{{ $box }}" aria-labelledby="slides-h">
        <h2 id="slides-h" class="text-lg font-bold text-steel-950">Slides ({{ $slides->count() }})</h2>
        <p class="mt-1 text-sm text-steel-700">Upload slide images (in PowerPoint: File → Export → PNG, all slides). They are stored privately and shown only to paid members, with their email as a small watermark.</p>
        <form method="post" action="/admin/course/lessons/{{ $lesson->id }}/slides" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <label class="grid gap-1 font-semibold text-steel-900">Images <input type="file" name="files[]" accept="image/png,image/jpeg,image/webp" multiple required class="font-normal"></label>
            <button type="submit" class="{{ $btn }} bg-steel-900 text-white">Upload slides</button>
        </form>
        @if ($slides->isNotEmpty())
            <ol class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($slides as $s)
                    <li class="rounded-[var(--radius-control)] border border-steel-200 p-2">
                        <img src="/courses/slide/{{ $s->id }}" alt="Slide {{ $loop->iteration }}" loading="lazy" class="w-full rounded border border-steel-100">
                        <p class="mt-1 text-sm font-semibold">Slide {{ $loop->iteration }} <code class="text-steel-600">[[slide:{{ $loop->iteration }}]]</code></p>
                        <form method="post" action="/admin/course/slides/{{ $s->id }}" class="mt-1 flex gap-2">@csrf<input type="hidden" name="op" value="caption"><input name="caption" value="{{ $s->caption }}" placeholder="Caption (optional)" class="min-h-11 flex-1 rounded border border-steel-400 px-2 text-sm"><button class="rounded border border-steel-400 px-2 text-sm">Save</button></form>
                        <div class="mt-2 flex flex-wrap gap-2 text-sm">
                            @foreach (['up' => '↑ Up', 'down' => '↓ Down', 'delete' => 'Delete'] as $op => $lbl)
                                <form method="post" action="/admin/course/slides/{{ $s->id }}">@csrf<input type="hidden" name="op" value="{{ $op }}"><button class="min-h-11 rounded border px-3 {{ $op === 'delete' ? 'border-danger-700 text-danger-700' : 'border-steel-400' }}">{{ $lbl }}</button></form>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <x-admin.confirm :action="'/admin/course/lessons/'.$lesson->id.'/delete'" title="Delete this lesson?" confirm-label="Delete lesson" label="Delete lesson">
        <p>The lesson, its text and all its slides are removed for good.</p>
    </x-admin.confirm>
</x-admin.layout>
