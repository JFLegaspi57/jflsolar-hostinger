{{-- A member's course record: the lessons they have worked on, with pass / fail and the date. $staff = also show each answer. --}}
@php
    $staff = $staff ?? false;
    $all = \App\Support\Course::lessons();
    $withQuiz = $all->count();
    $passedN = collect($record)->where('result.status', 'passed')->count();
    $badge = [
        'passed' => ['✅ Passed', 'background:#dcfce7;color:#166534'],
        'failed' => ['❌ Failed', 'background:#fee2e2;color:#991b1b'],
        'progress' => ['In progress', 'background:#eef1f5;color:#334155'],
    ];
    $verdict = ['correct' => '✅ Correct', 'partly' => '🟡 Partly correct', 'wrong' => '❌ Not quite'];
@endphp
<div class="mt-4">
    <p class="font-semibold text-steel-950">{{ $staff ? 'Course progress' : 'Your course record' }}: {{ $passedN }} of {{ $withQuiz }} lessons passed</p>
    @if (empty($record))
        <p class="mt-2 text-sm text-steel-700">{{ $staff ? 'No answers checked yet.' : 'No lessons done yet. Your results appear here once you check your answers at the end of a lesson.' }}</p>
    @else
        <div class="mt-2 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-steel-200 text-left text-steel-600">
                        <th class="py-2 pr-4 font-semibold">Lesson</th>
                        <th class="py-2 pr-4 font-semibold">Result</th>
                        <th class="py-2 pr-4 font-semibold">Score</th>
                        <th class="py-2 font-semibold">Last checked</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($record as $row)
                        @php $r = $row['result']; [$label, $style] = $badge[$r['status']] ?? $badge['progress']; @endphp
                        <tr class="border-b border-steel-200 align-top">
                            <td class="py-2 pr-4">
                                <a href="{{ \App\Support\Course::PATH }}/{{ $row['lesson']->slug }}" class="font-semibold text-steel-950 underline">{{ $row['lesson']->title }}</a>
                                @if ($staff && $row['answers'])
                                    <details class="mt-1">
                                        <summary class="cursor-pointer text-steel-700 underline">Answers</summary>
                                        <ol class="mt-2">
                                            @foreach ($row['answers'] as $a)
                                                <li class="mt-2"><span class="font-semibold">{{ $a['n'] }}. {{ $a['question'] }}</span> <span class="text-steel-600">{{ $verdict[$a['verdict']] ?? $a['verdict'] }}</span><br><span class="whitespace-pre-line text-steel-800">{{ $a['answer'] }}</span></li>
                                            @endforeach
                                        </ol>
                                    </details>
                                @endif
                            </td>
                            <td class="py-2 pr-4"><span class="inline-block rounded-full px-2 text-xs font-bold whitespace-nowrap" style="{{ $style }};padding-block:.15rem">{{ $label }}</span></td>
                            <td class="py-2 pr-4 tabular whitespace-nowrap">{{ $r['status'] === 'progress' ? $r['answered'].' of '.$r['total'].' checked' : \App\Support\CourseResults::points($r['score']).' of '.$r['total'].' ('.$r['percent'].'%)' }}</td>
                            <td class="py-2 whitespace-nowrap text-steel-700">{{ $row['last']?->setTimezone('Asia/Manila')->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if (! $staff && $passedN === $withQuiz && $withQuiz > 0)
            <p class="mt-3 font-semibold" style="color:#166534">🎉 You have passed every lesson. You can now request the assessment and OJT sessions for your certificate of completion.</p>
        @endif
    @endif
</div>
