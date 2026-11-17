{{-- One member's online course progress in the members list: lessons passed out of the total, and the lesson they last opened. --}}
@php $c = $course[$id] ?? null; @endphp
@if ($c && $c['total'] > 0)
    @php $pct = (int) round($c['passed'] / $c['total'] * 100); @endphp
    <div class="mt-1 text-sm text-steel-700">
        <span class="font-semibold text-steel-900">Online course:</span>
        @if ($c['started'] === 0 && ! $c['seen'] && ! ($c['opened'] ?? 0))
            <span class="text-steel-600">not started</span>
        @else
            {{ $c['passed'] }} of {{ $c['total'] }} lessons passed @if ($c['failed']) · {{ $c['failed'] }} failed @endif @if ($c['opened'] ?? 0) · {{ $c['opened'] }} {{ $c['opened'] === 1 ? 'lesson' : 'lessons' }} opened @endif
            @if ($c['seen']) · last opened Lesson {{ $c['seenNumber'] }}: {{ \Illuminate\Support\Str::limit($c['seen']->title, 48) }}@endif
            <span aria-hidden="true" style="display:block;max-width:16rem;height:.4rem;margin-top:.3rem;border-radius:999px;background:var(--color-steel-200);overflow:hidden"><span style="display:block;height:100%;width:{{ max($pct, $c['passed'] ? 3 : 0) }}%;background:#16a34a"></span></span>
        @endif
    </div>
@endif
