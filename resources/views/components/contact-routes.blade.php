@props(['settings', 'dark' => false])
@php
    $icons = ['phone' => 'phone', 'whatsapp' => 'chat', 'viber' => 'chat', 'messenger' => 'chat', 'facebook' => 'facebook', 'email' => 'mail', 'sales_email' => 'mail'];
    $all = collect(\App\Support\Contact::channels($settings));
    // The main and sales email addresses share one card.
    $emails = $all->whereIn('key', ['email', 'sales_email'])->values();
    $frame = 'flex min-h-14 items-center gap-3 rounded-[var(--radius-control)] border px-4 py-2.5 transition-colors '.($dark ? 'border-white/15 bg-white/5' : 'border-steel-200 bg-white');
    $hover = $dark ? 'hover:bg-white/10' : 'hover:border-steel-400';
    $bubble = 'inline-flex size-9 shrink-0 items-center justify-center rounded-full '.($dark ? 'bg-gold-500 text-steel-950' : 'bg-steel-900 text-white');
    $label = 'block text-xs font-semibold tracking-wide uppercase '.($dark ? 'text-steel-300' : 'text-steel-600');
@endphp
{{-- Direct contact routes (always usable, whatever happens to the form). --}}
<ul class="grid gap-3 sm:grid-cols-2">
    @foreach ($all as $c)
        @continue(in_array($c['key'], ['email', 'sales_email'], true) && $emails->count() > 1)
        @if (! empty($c['alt']))
            {{-- One number for two apps: each app name is its own link. --}}
            <li>
                <div class="{{ $frame }}">
                    <span class="{{ $bubble }}"><x-icon :name="$icons[$c['key']]" class="size-[1.1rem]" /></span>
                    <span class="min-w-0">
                        <span class="{{ $label }}">@foreach ($c['alt'] as $app => $href)@if (! $loop->first)/@endif<a href="{{ $href }}" @if ($app === 'WhatsApp') target="_blank" rel="noopener noreferrer" @endif class="underline-offset-2 hover:underline">{{ $app }}@if ($app === 'WhatsApp')<span class="sr-only"> (opens in a new tab)</span>@endif</a>@endforeach</span>
                        <a href="{{ $c['href'] }}" target="_blank" rel="noopener noreferrer" class="block font-semibold break-words tabular hover:underline">{{ $c['detail'] }}<span class="sr-only"> on WhatsApp (opens in a new tab)</span></a>
                    </span>
                </div>
            </li>
            @continue
        @endif
        <li>
            <a href="{{ $c['href'] }}" @if ($c['external']) target="_blank" rel="noopener noreferrer" @endif class="{{ $frame }} {{ $hover }}">
                <span class="{{ $bubble }}"><x-icon :name="$icons[$c['key']]" class="size-[1.1rem]" /></span>
                <span class="min-w-0">
                    <span class="{{ $label }}">{{ $c['label'] }}</span>
                    <span class="block font-semibold break-words tabular">{{ $c['detail'] }}</span>
                </span>
                @if ($c['external'])<span class="sr-only">(opens in a new tab)</span>@endif
            </a>
        </li>
    @endforeach
    @if ($emails->count() > 1)
        <li>
            <div class="{{ $frame }}">
                <span class="{{ $bubble }}"><x-icon name="mail" class="size-[1.1rem]" /></span>
                <span class="min-w-0">
                    <span class="{{ $label }}">Email</span>
                    @foreach ($emails as $e)
                        <a href="{{ $e['href'] }}" class="block font-semibold break-words hover:underline">{{ $e['detail'] }}</a>
                    @endforeach
                </span>
            </div>
        </li>
    @endif
</ul>
