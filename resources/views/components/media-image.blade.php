@props(['asset', 'sizes', 'priority' => false, 'admin' => false, 'fit' => 'cover'])
{{-- Responsive CMS image from the pre-generated WebP widths. --}}
<img src="{{ $asset->url($asset->bestWidth(1280), $admin) }}" srcset="{{ $asset->srcset($admin) }}" sizes="{{ $sizes }}" alt="{{ $asset->alt }}"
    @if ($asset->width) width="{{ $asset->width }}" @endif @if ($asset->height) height="{{ $asset->height }}" @endif
    loading="{{ $priority ? 'eager' : 'lazy' }}" @if ($priority) fetchpriority="high" @endif decoding="async"
    {{ $attributes->merge(['class' => 'h-full w-full '.($fit === 'contain' ? 'object-contain' : 'object-cover')]) }} />
