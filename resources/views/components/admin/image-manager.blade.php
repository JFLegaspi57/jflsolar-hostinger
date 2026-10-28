@props(['ownerType', 'owner', 'images', 'title' => 'Images', 'coverLabel' => 'cover', 'guidance' => null])
@php
    $guidanceDefault = [
        'package' => 'Use equipment or system photos you have permission to publish.',
        'product' => 'Use clear product photos — ideally the manufacturer’s official images or your own photos.',
        'project' => 'Use only real JFL Solar project photos that you have permission to publish. Never use stock photos or other companies’ work.',
        'block' => 'Only use images you have permission to publish.',
    ];
    $btn = 'inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-steel-300 bg-white px-3 text-sm font-semibold text-steel-900 hover:border-steel-900 disabled:opacity-40';
    $input = 'min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base';
    $upload = $errors->getBag('upload');
    $showCover = $ownerType !== 'block';
    $count = $images->count();
    $ownerKey = $owner->getKey();
@endphp
<section id="images" aria-labelledby="images-title" class="scroll-mt-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5 sm:p-6">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="images-title" class="text-lg font-bold text-steel-950">{{ $title }}</h2>
        <p class="rounded-full bg-steel-100 px-2.5 py-0.5 text-xs font-semibold text-steel-700">Saved separately — changes here apply immediately</p>
    </div>
    <p class="mt-1 text-sm text-steel-600">{{ $guidance ?? $guidanceDefault[$ownerType] }} JPEG, PNG, WebP or AVIF; large photos are reduced automatically. Images become public only when this item is published.</p>

    @if (session('media_status'))
        <p role="status" class="mt-4 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 px-3 py-2 text-sm font-semibold text-ok-700">{{ session('media_status') }}</p>
    @elseif (session('media_error'))
        <p role="alert" class="mt-4 rounded-[var(--radius-control)] border-2 border-danger-700 bg-danger-50 px-3 py-2 text-sm font-semibold text-danger-700">{{ session('media_error') }}</p>
    @endif

    @if ($count)
        <ol class="mt-5 space-y-4">
            @foreach ($images as $i => $img)
                @php
                    $bag = $errors->getBag('img'.$img->id);
                    $repError = $errors->getBag('rep'.$img->id)->first('file');
                    $mine = old('asset') === $img->id;
                    $alt = $mine ? old('alt') : $img->alt;
                    $caption = $mine ? old('caption') : $img->caption;
                @endphp
                <li id="img-{{ $img->id }}" class="grid scroll-mt-6 gap-4 rounded-[var(--radius-control)] border border-steel-200 p-3 sm:grid-cols-[10rem_1fr]">
                    <div>
                        <img src="{{ $img->url($img->bestWidth(640), true) }}" alt="{{ $img->alt }}" class="aspect-[4/3] w-full rounded bg-steel-100 object-cover">
                        @if ($img->is_cover && $showCover)<p class="mt-1 text-xs font-bold text-gold-800 uppercase">★ {{ $coverLabel }}</p>@endif
                        <p class="mt-1 text-xs text-steel-600 tabular">{{ $img->width }}×{{ $img->height }}</p>
                    </div>
                    <div class="space-y-3">
                        <form method="post" action="/admin/media/{{ $img->id }}/text" data-image-text @if ($mine) data-dirty-on-load @endif class="grid gap-2 sm:grid-cols-2">
                            @csrf
                            <div>
                                <label for="alt-{{ $img->id }}" class="block text-sm font-semibold">Alternative text</label>
                                <input id="alt-{{ $img->id }}" name="alt" value="{{ $alt }}" maxlength="300" class="{{ $input }}" @if ($bag->has('alt')) aria-invalid="true" aria-describedby="alt-{{ $img->id }}-error" @endif>
                                @if ($bag->has('alt'))<p id="alt-{{ $img->id }}-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $bag->first('alt') }}</p>@endif
                            </div>
                            <div>
                                <label for="cap-{{ $img->id }}" class="block text-sm font-semibold">Caption</label>
                                <input id="cap-{{ $img->id }}" name="caption" value="{{ $caption }}" maxlength="300" class="{{ $input }}">
                            </div>
                            <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                                <button type="submit" class="{{ $btn }}">Save image details</button>
                                <p class="text-sm font-semibold text-warn-800" data-unsaved-note @unless ($mine) hidden @endunless>Unsaved changes</p>
                            </div>
                        </form>

                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($showCover && ! $img->is_cover)
                                <form method="post" action="/admin/media/{{ $img->id }}/op">@csrf<input type="hidden" name="op" value="cover"><button type="submit" class="{{ $btn }}">Make {{ $coverLabel }}</button></form>
                            @endif
                            <form method="post" action="/admin/media/{{ $img->id }}/op">@csrf<input type="hidden" name="op" value="up"><button type="submit" @disabled($i === 0) class="{{ $btn }}" aria-label="Move image earlier">↑ Earlier</button></form>
                            <form method="post" action="/admin/media/{{ $img->id }}/op">@csrf<input type="hidden" name="op" value="down"><button type="submit" @disabled($i === $count - 1) class="{{ $btn }}" aria-label="Move image later">↓ Later</button></form>
                            <x-admin.confirm :action="'/admin/media/'.$img->id.'/op'" :fields="['op' => 'delete']" title="Delete this image?" confirm-label="Delete image" label="Delete" class="!min-h-11 !border !px-3">
                                <p>The file is removed permanently. This cannot be undone.</p>
                            </x-admin.confirm>
                        </div>

                        <details @if ($repError) open @endif>
                            <summary class="inline-flex min-h-11 cursor-pointer items-center text-sm font-semibold text-steel-900 underline">Replace file</summary>
                            <form method="post" action="/admin/media/{{ $img->id }}/replace" enctype="multipart/form-data" class="mt-2 space-y-2">
                                @csrf
                                <label for="rep-{{ $img->id }}" class="block text-sm font-semibold">New image file</label>
                                <input id="rep-{{ $img->id }}" name="file" type="file" accept="image/jpeg,image/png,image/webp,image/avif" required data-prepare-image @if ($repError) aria-invalid="true" aria-describedby="rep-{{ $img->id }}-error" @endif class="block w-full text-sm file:mr-3 file:min-h-11 file:rounded-[var(--radius-control)] file:border-0 file:bg-steel-900 file:px-4 file:font-semibold file:text-white">
                                @if ($repError)<p id="rep-{{ $img->id }}-error" role="alert" class="text-sm font-semibold text-danger-700">{{ $repError }}</p>@endif
                                <button type="submit" class="{{ $btn }}" data-pending-label="Uploading…">Replace image</button>
                            </form>
                        </details>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    <form method="post" action="/admin/media/upload/{{ $ownerType }}/{{ $ownerKey }}" enctype="multipart/form-data" class="mt-6 grid gap-4 rounded-[var(--radius-control)] border border-dashed border-steel-400 bg-steel-50 p-4 sm:grid-cols-2">
        @csrf
        <h3 class="font-bold text-steel-950 sm:col-span-2">Add an image</h3>
        <div class="sm:col-span-2">
            <label for="upload-file" class="mb-1 block font-semibold">Image file <span class="text-sm font-normal text-steel-600">(required)</span></label>
            <input id="upload-file" name="file" type="file" accept="image/jpeg,image/png,image/webp,image/avif" required data-prepare-image data-preview="upload-preview"
                @if ($upload->has('file')) aria-invalid="true" aria-describedby="upload-file-error" @endif
                class="block w-full text-sm file:mr-3 file:min-h-11 file:rounded-[var(--radius-control)] file:border-0 file:bg-steel-900 file:px-4 file:font-semibold file:text-white">
            @if ($upload->has('file'))<p id="upload-file-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $upload->first('file') }}</p>@endif
            <img id="upload-preview" alt="" hidden class="mt-3 h-32 w-auto rounded border border-steel-200 object-cover">
        </div>
        <div>
            <label for="upload-alt" class="mb-1 block font-semibold">Alternative text <span class="text-sm font-normal text-steel-600">(required)</span></label>
            <input id="upload-alt" name="alt" value="{{ old('asset') ? '' : old('alt') }}" maxlength="300" class="{{ $input }}"
                aria-describedby="upload-alt-hint @if ($upload->has('alt')) upload-alt-error @endif" @if ($upload->has('alt')) aria-invalid="true" @endif>
            <p id="upload-alt-hint" class="mt-1 text-sm text-steel-600">Describe what the photo shows, for people who cannot see it.</p>
            @if ($upload->has('alt'))<p id="upload-alt-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $upload->first('alt') }}</p>@endif
        </div>
        <div>
            <label for="upload-caption" class="mb-1 block font-semibold">Caption</label>
            <input id="upload-caption" name="caption" value="{{ old('asset') ? '' : old('caption') }}" maxlength="300" class="{{ $input }}">
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
            <button type="submit" data-pending-label="Uploading…" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-steel-900 px-5 font-semibold text-white hover:bg-steel-800 disabled:opacity-60">Upload image</button>
        </div>
    </form>
</section>
