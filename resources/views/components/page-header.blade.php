@props(['eyebrow' => null, 'title', 'intro' => null])
{{-- Page header band used by inner pages. --}}
<div class="border-b border-steel-200 bg-steel-50">
    <x-container class="py-12 sm:py-16">
        <x-section-heading as="h1" :eyebrow="$eyebrow" :title="$title" :intro="$intro" />
        @if ($slot->isNotEmpty())
            <div class="mt-6">{{ $slot }}</div>
        @endif
    </x-container>
</div>
