@props(['title', 'intro' => null, 'back' => null, 'backLabel' => null])
<div class="mb-8">
    @if ($back)
        <a href="{{ $back }}" class="mb-3 inline-flex min-h-11 items-center text-sm font-semibold text-steel-700 underline hover:text-steel-950">← {{ $backLabel }}</a>
    @endif
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-steel-950">{{ $title }}</h1>
            @if ($intro)<div class="mt-2 max-w-2xl text-steel-600">{{ $intro }}</div>@endif
        </div>
        {{ $action ?? '' }}
    </div>
</div>
