@props(['type'])
@if ($type === 'installed_system')
    <span class="inline-flex items-center gap-1.5 rounded bg-steel-900 px-2 py-1 text-xs font-bold tracking-wide text-white uppercase">
        <x-icon name="tools" class="size-3.5" /> {{ \App\Support\Labels::PACKAGE_TYPE[$type] }}
    </span>
@else
    <span class="inline-flex items-center gap-1.5 rounded border-2 border-gold-500 bg-gold-50 px-2 py-0.5 text-xs font-bold tracking-wide text-gold-800 uppercase">
        <x-icon name="battery" class="size-3.5" /> {{ \App\Support\Labels::PACKAGE_TYPE[$type] }}
    </span>
@endif
