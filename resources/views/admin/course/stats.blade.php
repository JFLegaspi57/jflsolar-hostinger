{{-- Admin → Online course: how many members have started the course, how far they got, and how many reached each lesson. --}}
@php
    $s = $stats;
    $pct = fn ($n, $of) => $of > 0 ? (int) round($n / $of * 100) : 0;
    $tile = 'border-radius:var(--radius-panel);border:1px solid var(--color-steel-200);padding:.75rem 1rem;background:var(--color-steel-50)';
    $maxOpened = max(1, collect($s['lessons'])->max('opened'));
@endphp
<section class="mb-6 border border-steel-200 bg-white p-4 sm:p-6" style="border-radius:var(--radius-panel)" aria-labelledby="stats-h">
    <h2 id="stats-h" class="text-lg font-bold text-steel-950">Who is taking the course</h2>
    <p class="mt-1 text-sm text-steel-700">Live numbers from the members' course records. "Started" means the member has opened at least one lesson. A lesson is passed with at least {{ \App\Support\CourseResults::PASS_PERCENT }}% in its "Check your understanding" questions.</p>

    @php $qs = \App\Support\CourseQuiz::todayStats(); @endphp
    <p class="mt-3 text-sm" style="{{ $qs['failed'] ? 'color:#9a3412' : '' }}"><strong>Answer checks today:</strong> {{ number_format($qs['checked']) }} checked{{ $qs['failed'] ? ', '.number_format($qs['failed']).' could not be checked even after 3 tries' : ', none failed' }}{{ ($qs['retried'] ?? 0) ? ' ('.number_format($qs['retried']).' '.(($qs['retried'] ?? 0) === 1 ? 'try' : 'tries').' went wrong and worked on the next try; the student still got a result)' : '' }}.@if ($qs['problem']) <span class="text-steel-700">Last problem: {{ $qs['problem'] }}</span>@endif</p>
    <div class="mt-4 grid gap-3" style="grid-template-columns:repeat(auto-fit,minmax(10rem,1fr))">
        <div style="{{ $tile }}"><div class="text-sm text-steel-700">Started the course</div><div class="text-2xl font-bold text-steel-950 tabular">{{ number_format($s['started']) }}</div><div class="text-sm text-steel-600">{{ $pct($s['started'], $s['members']) }}% of {{ number_format($s['members']) }} members</div></div>
        <div style="{{ $tile }}"><div class="text-sm text-steel-700">Not started yet</div><div class="text-2xl font-bold text-steel-950 tabular">{{ number_format($s['notStarted']) }}</div><div class="text-sm text-steel-600">{{ $pct($s['notStarted'], $s['members']) }}% of members</div></div>
        <div style="{{ $tile }}"><div class="text-sm text-steel-700">Started in the last 7 days</div><div class="text-2xl font-bold text-steel-950 tabular">{{ number_format($s['startedWeek']) }}</div></div>
        <div style="{{ $tile }}"><div class="text-sm text-steel-700">Passed at least 1 lesson</div><div class="text-2xl font-bold text-steel-950 tabular">{{ number_format($s['passedAny']) }}</div><div class="text-sm text-steel-600">best so far: {{ $s['maxPassed'] }} of {{ count($s['lessons']) }} lessons</div></div>
        <div style="{{ $tile }}"><div class="text-sm text-steel-700">Passed every lesson</div><div class="text-2xl font-bold text-steel-950 tabular">{{ number_format($s['passedAll']) }}</div></div>
    </div>

    @php $tm = $s['timing']; $dur = fn ($x) => \App\Support\CourseResults::duration($x); @endphp
    <h3 class="mt-6 font-bold text-steel-950">How long students take</h3>
    <p class="mt-1 text-sm text-steel-700">A lesson counts from the moment the student first opened it until the answer check that passed it. The whole course counts from the first lesson opened until the last lesson passed, for students who passed every lesson. "Typical" is the middle value (half are faster, half slower), which is not pulled up by students who left a lesson for weeks.</p>
    <div class="mt-3 grid gap-3" style="grid-template-columns:repeat(auto-fit,minmax(13rem,1fr))">
        <div style="{{ $tile }}"><div class="text-sm text-steel-700">Average time to finish a lesson</div><div class="text-2xl font-bold text-steel-950 tabular">{{ $dur($tm['lessonAvg']) }}</div><div class="text-sm text-steel-600">@if ($tm['lessonCount']) typical: {{ $dur($tm['lessonMedian']) }} · from {{ number_format($tm['lessonCount']) }} passed {{ $tm['lessonCount'] === 1 ? 'lesson' : 'lessons' }} @else no lesson passed yet @endif</div></div>
        <div style="{{ $tile }}"><div class="text-sm text-steel-700">Average time to finish the whole course</div><div class="text-2xl font-bold text-steel-950 tabular">{{ $dur($tm['courseAvg']) }}</div><div class="text-sm text-steel-600">@if ($tm['courseCount']) typical: {{ $dur($tm['courseMedian']) }} · from {{ number_format($tm['courseCount']) }} {{ $tm['courseCount'] === 1 ? 'student' : 'students' }} who passed every lesson @else nobody has passed every lesson yet @endif</div></div>
    </div>

    <h3 class="mt-6 font-bold text-steel-950">How far the members who started have gone</h3>
    <table class="mt-2 w-full text-sm" style="max-width:34rem">
        @foreach ($s['bands'] as $label => $n)
            <tr class="border-t border-steel-200">
                <td class="py-2 pr-3">{{ $label }} opened</td>
                <td class="py-2 pr-3 text-right font-semibold tabular" style="width:4rem">{{ number_format($n) }}</td>
                <td class="py-2" style="width:45%"><span aria-hidden="true" style="display:block;height:.55rem;border-radius:999px;background:var(--color-steel-200);overflow:hidden"><span style="display:block;height:100%;width:{{ $pct($n, max(1, $s['started'])) }}%;background:#2563eb"></span></span></td>
            </tr>
        @endforeach
    </table>

    <h3 class="mt-6 font-bold text-steel-950">Members leading the course</h3>
    <p class="mt-1 text-sm text-steel-700">Everyone who has started, ranked by lessons passed, then lessons opened, then the most recent activity. "Started" is the day and time (Philippine time) they first opened a lesson; students who passed every lesson also show how long they took. Open a name to see the member's full course record.</p>
    @php $rank = $s['ranking']; $row = function ($r, $i) use ($s) {
        $at = $r['at'] ? \Illuminate\Support\Carbon::parse($r['at'])->timezone('Asia/Manila')->format('j M, g:i A') : '—';
        $st = ($r['started'] ?? null) ? \Illuminate\Support\Carbon::parse($r['started'])->timezone('Asia/Manila')->format('D j M Y, g:i A') : '—';
        $took = ($r['took'] ?? null) !== null ? 'Finished in '.\App\Support\CourseResults::duration($r['took']) : '';
        return '<tr class="border-t border-steel-200"><td class="py-2 pr-3 tabular text-steel-600">'.($i + 1).'</td>'
            .'<td class="py-2 pr-3"><a class="font-semibold underline" href="/admin/members/'.$r['id'].'">'.e($r['name'] ?: $r['email']).'</a><br><span class="text-steel-600">'.e($r['email']).'</span></td>'
            .'<td class="pr-3 text-right tabular font-semibold">'.$r['passed'].'</td>'
            .'<td class="pr-3 text-right tabular">'.$r['opened'].'</td>'
            .'<td class="pr-3 tabular">'.($r['last'] ? 'Lesson '.$r['last'] : '—').'</td>'
            .'<td class="pr-3 whitespace-nowrap text-steel-700">'.$st.($took ? '<br><span class="font-semibold text-steel-900">'.e($took).'</span>' : '').'</td>'
            .'<td class="pr-3 whitespace-nowrap text-steel-700">'.$at.'</td>'
            .'<td><form method="post" action="/admin/course/hide/'.$r['id'].'"><input type="hidden" name="_token" value="'.csrf_token().'"><input type="hidden" name="hide" value="1"><button type="submit" class="text-sm underline text-steel-700" title="Leave this member out of the course numbers and ranking">Hide</button></form></td></tr>';
    }; $head = '<tr class="text-left text-steel-600"><th class="py-1 pr-3">#</th><th class="pr-3">Member</th><th class="pr-3 text-right">Passed</th><th class="pr-3 text-right">Opened</th><th class="pr-3">Last opened</th><th class="pr-3">Started</th><th class="pr-3">Last active</th><th></th></tr>'; @endphp
    @if (! $rank)
        <p class="mt-2 text-steel-600">Nobody has started yet.</p>
    @else
        <div class="mt-2 overflow-x-auto"><table class="w-full text-sm">{!! $head !!}@foreach (array_slice($rank, 0, 20) as $i => $r){!! $row($r, $i) !!}@endforeach</table></div>
        @if (count($rank) > 20)
            <details class="mt-2">
                <summary class="cursor-pointer font-semibold text-steel-900 underline">Show all {{ number_format(count($rank)) }} members who started</summary>
                <div class="mt-2 overflow-x-auto"><table class="w-full text-sm">{!! $head !!}@foreach (array_slice($rank, 20, null, true) as $i => $r){!! $row($r, $i) !!}@endforeach</table></div>
            </details>
        @endif
    @endif

    @if ($s['hidden'])
        <details class="mt-3 text-sm">
            <summary class="cursor-pointer font-semibold text-steel-900">Hidden from the course progress and from "Online now" ({{ count($s['hidden']) }})</summary>
            <ul class="mt-2">
                @foreach ($s['hidden'] as $h)
                    <li class="flex flex-wrap items-center gap-2 py-1">{{ $h['name'] ?: $h['email'] }} <span class="text-steel-600">{{ $h['email'] }}</span>
                        <form method="post" action="/admin/course/hide/{{ $h['id'] }}">@csrf<input type="hidden" name="hide" value="0"><button type="submit" class="underline">Show again</button></form></li>
                @endforeach
            </ul>
        </details>
    @endif

    <details class="mt-6">
        <summary class="cursor-pointer font-bold text-steel-950">Members per lesson (where they stop, and how long each lesson takes)</summary>
        <div class="mt-2 overflow-x-auto"><table class="w-full text-sm">
            <tr class="text-left text-steel-600"><th class="py-1 pr-3">Lesson</th><th class="pr-3 text-right">Opened</th><th class="pr-3 text-right">Passed</th><th class="pr-3 text-right">Average time to pass</th><th style="width:25%"></th></tr>
            @foreach ($s['lessons'] as $l)
                <tr class="border-t border-steel-200">
                    <td class="py-2 pr-3">{{ $l['number'] }}. {{ \Illuminate\Support\Str::limit($l['title'], 70) }}</td>
                    <td class="pr-3 text-right tabular">{{ number_format($l['opened']) }}</td>
                    <td class="pr-3 text-right tabular">{{ number_format($l['passed']) }}</td>
                    <td class="pr-3 text-right tabular whitespace-nowrap">{{ \App\Support\CourseResults::duration($l['avg'] ?? null) }}</td>
                    <td><span aria-hidden="true" style="display:block;height:.55rem;border-radius:999px;background:var(--color-steel-200);overflow:hidden"><span style="display:block;height:100%;width:{{ $pct($l['opened'], $maxOpened) }}%;background:#2563eb"></span></span></td>
                </tr>
            @endforeach
        </table></div>
    </details>
    <p class="mt-4 text-sm"><a href="/admin/members" class="underline">See each member's progress in Members</a></p>
</section>
