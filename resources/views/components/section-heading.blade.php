@props(['id' => null, 'eyebrow' => null, 'title', 'intro' => null, 'dark' => false, 'as' => 'h2'])
<div {{ $attributes->merge(['class' => 'max-w-3xl']) }}>
    @if ($eyebrow)
        <p class="mb-4 flex items-center gap-3 text-sm font-semibold tracking-wide uppercase {{ $dark ? 'text-gold-400' : 'text-gold-800' }}">
            <span class="rule-gold" aria-hidden="true"></span>{{ $eyebrow }}
        </p>
    @endif
    <{{ $as }} @if ($id) id="{{ $id }}" @endif class="{{ $as === 'h1' ? 'text-4xl sm:text-5xl' : 'text-3xl sm:text-4xl' }} font-extrabold {{ $dark ? 'text-white' : 'text-steel-950' }}">{{ $title }}</{{ $as }}>
    @if ($intro)
        <div class="mt-4 text-lg {{ $dark ? 'text-steel-200' : 'text-steel-600' }}">{{ $intro }}</div>
    @endif
</div>
