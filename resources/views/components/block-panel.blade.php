@props(['block', 'image' => null])
<section aria-labelledby="b-{{ $block->key }}" class="overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6">
    @if ($image)
        <div class="-mx-6 -mt-6 mb-5 aspect-[16/9] bg-steel-100"><x-media-image :asset="$image" sizes="(min-width: 768px) 560px, 100vw" /></div>
    @endif
    @if ($block->subtitle && $block->section === 'why')
        <p class="text-xs font-semibold tracking-wide text-gold-800 uppercase">{{ $block->subtitle }}</p>
    @endif
    <h3 id="b-{{ $block->key }}" class="mt-1 text-xl font-bold text-steel-950">{{ $block->title }}</h3>
    <x-claim-marker :block="$block" class="mt-1.5" />
    <x-paragraphs :text="$block->body" class="mt-2 text-steel-700" />
</section>
