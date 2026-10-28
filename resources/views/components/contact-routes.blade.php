@props(['settings', 'dark' => false])
@php
    $icons = ['phone' => 'phone', 'whatsapp' => 'chat', 'viber' => 'chat', 'messenger' => 'chat', 'facebook' => 'facebook', 'email' => 'mail'];
@endphp
{{-- Direct contact routes (always usable, whatever happens to the form). --}}
<ul class="grid gap-3 sm:grid-cols-2">
    @foreach (\App\Support\Contact::channels($settings) as $c)
        <li>
            <a href="{{ $c['href'] }}" @if ($c['external']) target="_blank" rel="noopener noreferrer" @endif
                class="flex min-h-14 items-center gap-3 rounded-[var(--radius-control)] border px-4 py-2.5 transition-colors {{ $dark ? 'border-white/15 bg-white/5 hover:bg-white/10' : 'border-steel-200 bg-white hover:border-steel-400' }}">
                <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full {{ $dark ? 'bg-gold-500 text-steel-950' : 'bg-steel-900 text-white' }}">
                    <x-icon :name="$icons[$c['key']]" class="size-[1.1rem]" />
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-semibold tracking-wide uppercase {{ $dark ? 'text-steel-300' : 'text-steel-600' }}">{{ $c['label'] }}</span>
                    <span class="block font-semibold break-words tabular">{{ $c['detail'] }}</span>
                </span>
                @if ($c['external'])<span class="sr-only">(opens in a new tab)</span>@endif
            </a>
        </li>
    @endforeach
</ul>
