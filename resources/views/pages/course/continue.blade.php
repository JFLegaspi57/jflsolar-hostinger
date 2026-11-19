{{-- "Continue your course": brings the member back to the next lesson (after the last one finished, or the one left open). --}}
@php
    $s = $nextStep ?? null;
    $url = fn ($l) => \App\Support\Course::PATH.'/'.$l->slug;
@endphp
@if ($s && ($s['lesson'] || $s['kind'] === 'complete'))
    <div class="{{ $class ?? '' }} bg-white p-5 text-steel-800" style="border-radius: .75rem; border: 2px solid var(--color-gold-500); box-shadow: 0 4px 14px rgb(15 23 42 / .08)">
        <p class="text-sm font-semibold tracking-wide text-gold-800 uppercase">Online Course · The Fundamentals of Solar PV System</p>
        @if ($s['kind'] === 'complete')
            <p class="mt-1 text-lg font-bold text-steel-950">🎉 You have passed every lesson!</p>
            <p class="mt-1">You can now request the assessment and OJT sessions for your certificate of completion.</p>
        @else
            @if ($s['kind'] === 'next')
                <p class="mt-1 text-lg font-bold text-steel-950">Welcome back! You finished Lesson {{ $s['doneNumber'] }}: {{ $s['done']->title }}.</p>
                <p class="mt-1">Next up is <strong>Lesson {{ $s['number'] }}: {{ $s['lesson']->title }}</strong>.</p>
            @elseif ($s['kind'] === 'resume')
                <p class="mt-1 text-lg font-bold text-steel-950">Welcome back! Continue where you left off.</p>
                <p class="mt-1">@if ($s['done'])You finished Lesson {{ $s['doneNumber'] }}. @endif You were on <strong>Lesson {{ $s['number'] }}: {{ $s['lesson']->title }}</strong>.</p>
            @else
                <p class="mt-1 text-lg font-bold text-steel-950">Start the course</p>
                <p class="mt-1">Begin with <strong>Lesson 1: {{ $s['lesson']->title }}</strong>. Take it easy and go at your own pace.</p>
            @endif
            <p class="mt-3"><a href="{{ $url($s['lesson']) }}" class="{{ \App\Support\Ui::button('primary') }}">{{ $s['kind'] === 'start' ? 'Start Lesson 1' : ($s['kind'] === 'resume' ? 'Continue Lesson '.$s['number'] : 'Start Lesson '.$s['number']) }} →</a></p>
        @endif
    </div>
@endif
