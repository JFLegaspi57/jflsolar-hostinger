{{-- The end-of-lesson result: passed or failed, from the member's latest checked answers. Also sent back after every check. --}}
@php
    use App\Support\CourseResults;
    $pass = CourseResults::PASS_PERCENT;
    $list = implode(', ', $r['missed']);
    $pts = CourseResults::points($r['score']);
@endphp
<section id="quiz-summary" class="quiz-summary is-{{ $r['status'] }}" aria-live="polite" aria-labelledby="quiz-summary-h">
    <h2 id="quiz-summary-h">{{ $tl ? 'Resulta mo sa araling ito' : 'Your result for this lesson' }}</h2>
    @if ($r['status'] === 'passed' || $r['status'] === 'failed')
        <p class="quiz-verdict">{{ $r['status'] === 'passed' ? ($tl ? '✅ PUMASA' : '✅ PASSED') : ($tl ? '❌ HINDI PUMASA' : '❌ FAILED') }}</p>
        <p>{{ $tl ? 'Iskor' : 'Score' }}: <strong>{{ $pts }} {{ $tl ? 'sa' : 'of' }} {{ $r['total'] }}</strong> ({{ $r['percent'] }}%) · {{ $tl ? 'Kailangan para pumasa' : 'Pass mark' }}: {{ $pass }}%</p>
        @if ($r['status'] === 'passed')
            <p>{{ $tl ? 'Magaling! Tapos mo na ang araling ito.' : 'Well done! You have completed this lesson.' }}@if ($list) {{ $tl ? 'Puwede mo pang balikan ang tanong '.$list.'.' : 'You may still review question'.(count($r['missed']) > 1 ? 's ' : ' ').$list.'.' }}@endif</p>
        @else
            <p>{{ $tl ? 'Balikan ang aralin, lalo na ang tanong '.$list.', at suriin ulit ang mga sagot mo. Ang pinakahuling sagot mo sa bawat tanong ang binibilang.' : 'Review the lesson, especially question'.(count($r['missed']) > 1 ? 's ' : ' ').$list.', then check your answers again. Your latest answer to each question counts.' }}</p>
        @endif
        <p class="quiz-summary-small">{{ $tl ? 'Tama = 1 puntos, halos tama = ½, hindi pa tama = 0.' : 'Correct = 1 point, partly correct = ½, not quite = 0.' }}</p>
    @elseif ($r['status'] === 'progress')
        <p>{{ $tl ? 'Nasuri mo na ang '.$r['answered'].' sa '.$r['total'].' na sagot. Suriin ang natitira para makita kung pumasa ka.' : 'You have checked '.$r['answered'].' of '.$r['total'].' answers. Check the rest to see whether you passed.' }} {{ $tl ? 'Kailangan para pumasa' : 'Pass mark' }}: {{ $pass }}%.</p>
    @else
        <p>{{ $tl ? 'Sagutin at suriin ang lahat ng '.$r['total'].' tanong sa itaas para malaman kung pumasa ka sa araling ito.' : 'Answer and check all '.$r['total'].' questions above to see whether you passed this lesson.' }} {{ $tl ? 'Kailangan para pumasa' : 'Pass mark' }}: {{ $pass }}%.</p>
    @endif
</section>
