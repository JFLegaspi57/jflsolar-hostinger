<x-admin.layout title="Preview">
    <div role="note" class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-control)] border-2 border-dashed border-gold-600 bg-gold-50 px-4 py-3">
        <p class="text-steel-900"><strong>Staff preview</strong> — status: <strong class="capitalize">{{ $record->status }}</strong>. {{ $record->status === 'published' ? 'This is live on the website.' : 'This is not visible to the public.' }} Buttons on this page lead to the public site.</p>
        <a href="/admin/{{ $entity }}/{{ $record->id }}" class="inline-flex min-h-11 items-center font-semibold text-steel-900 underline">← Back to editing</a>
    </div>
    <div class="overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-white">
        @if ($entity === 'packages')
            <x-detail.package :pkg="$record" :settings="$settings" admin />
        @elseif ($entity === 'products')
            <x-detail.product :product="$record" :settings="$settings" admin />
        @else
            <x-detail.project :project="$record" admin />
        @endif
    </div>
</x-admin.layout>
