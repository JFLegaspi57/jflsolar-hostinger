@props(['label' => 'Placeholder image', 'detail' => null, 'icon' => 'panel'])
{{-- Clearly labelled placeholder for missing imagery — never presented as a real project photo. --}}
<div role="img" aria-label="{{ $label }}{{ $detail ? ' — '.$detail : '' }}" {{ $attributes->merge(['class' => 'pv-grid relative flex h-full w-full items-end overflow-hidden']) }}>
    <svg viewBox="0 0 120 80" aria-hidden="true" class="absolute inset-0 m-auto h-1/2 w-1/2">
        <g fill="none" stroke="rgb(255 255 255 / 0.28)" stroke-width="1.5">
            @switch($icon)
                @case('inverter')
                    <rect x="38" y="12" width="44" height="56" rx="4" /><rect x="48" y="24" width="24" height="12" rx="2" /><path d="M50 58h4M58 58h4M66 58h4" />
                    @break
                @case('battery')
                    <rect x="36" y="14" width="48" height="54" rx="4" /><path d="M52 8h16v6H52zM60 30v18M51 39h18" />
                    @break
                @case('hardware')
                    <path d="M14 50h92M14 58h92M30 50v-14h14v14M76 50v-14h14v14" />
                    @break
                @default
                    <path d="M20 62 34 18h52l14 44Z" /><path d="M27 40h66M24 51h72M48 18l-6 44M72 18l6 44" /><circle cx="98" cy="12" r="6" stroke="#ffb004" />
            @endswitch
        </g>
    </svg>
    <span class="relative m-3 inline-flex items-center gap-1.5 rounded bg-white/95 px-2 py-1 text-[0.7rem] font-semibold tracking-wide text-steel-900 uppercase">
        <span aria-hidden="true" class="size-1.5 rounded-full bg-gold-500"></span>{{ $label }}
    </span>
</div>
