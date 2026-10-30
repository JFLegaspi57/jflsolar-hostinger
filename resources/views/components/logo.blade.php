@props(['plate' => false, 'priority' => false])
{{-- Owner-supplied lockup on a transparent background; on dark surfaces pass plate. --}}
<a href="/" aria-label="JFL Solar — home" {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center rounded-[var(--radius-control)]'.($plate ? ' bg-white p-2.5' : '')]) }}>
    <img src="/brand/jfl-renewables-logo-272.png" srcset="/brand/jfl-renewables-logo-190.png 190w, /brand/jfl-renewables-logo-272.png 272w"
        sizes="(min-width: 1024px) 190px, (min-width: 640px) 150px, 132px" width="272" height="80" alt=""
        @if ($priority) fetchpriority="high" @else loading="lazy" @endif decoding="async" class="h-auto w-[132px] sm:w-[150px] lg:w-[190px]" />
</a>
