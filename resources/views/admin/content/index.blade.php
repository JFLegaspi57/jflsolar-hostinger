@php
    use App\Admin\Blocks;
    $sectionNames = ['about' => 'About page', 'why' => 'Why choose us', 'home' => 'Home page', 'services' => 'Services page', 'legal' => 'Legal pages'];
    $tones = ['shown' => 'bg-ok-50 text-ok-700', 'pending' => 'bg-warn-50 text-warn-800', 'hidden' => 'bg-steel-100 text-steel-700'];
@endphp
<x-admin.layout title="Site content">
    <x-admin.page-title title="Site content" intro="Company story, mission, vision, founder profile and the “Why choose us” points. Text marked “awaiting confirmation” shows only on the preview site until the owner confirms it.">
        <x-slot:action><x-admin.new-button href="/admin/content/new">New block</x-admin.new-button></x-slot:action>
    </x-admin.page-title>
    <div class="space-y-8">
        @foreach ($blocks->groupBy('section') as $section => $items)
            <section aria-labelledby="sec-{{ $section }}">
                <h2 id="sec-{{ $section }}" class="mb-3 text-xl font-bold text-steel-950">{{ $sectionNames[$section] ?? $section }}</h2>
                <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
                    @foreach ($items as $b)
                        @php $v = Blocks::visibility($b->published, $b->claim_status); @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <a href="/admin/content/{{ rawurlencode($b->key) }}" class="font-bold text-steel-950 underline-offset-2 hover:underline">{{ $b->title }}</a>
                                <p class="text-sm text-steel-600">{{ Blocks::placement($b->key, $b->section) }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $tones[$v['tone']] }}">{{ $v['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
</x-admin.layout>
