@props(['text'])
{{-- Plain-text body → paragraphs. No HTML is rendered.
     Each line is its own paragraph. Lines starting with "*", "-" or "•" become a ticked list.
     A short label before a colon ("Our Promise: ...") is shown in bold. --}}
@php
    $groups = [];
    foreach (preg_split('/\R/', (string) $text) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^[*\-•]\s+(.+)$/u', $line, $m)) {
            if (! $groups || end($groups)['type'] !== 'list') {
                $groups[] = ['type' => 'list', 'items' => []];
            }
            $groups[array_key_last($groups)]['items'][] = $m[1];
        } else {
            $groups[] = ['type' => 'p', 'text' => $line];
        }
    }
    $split = function (string $s): array {
        return preg_match('/^([^:]{2,40}):(\s+(.*))?$/u', $s, $m) && ! str_contains($m[1], '.')
            ? [$m[1].':', $m[3] ?? '']
            : [null, $s];
    };
@endphp
<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @foreach ($groups as $g)
        @if ($g['type'] === 'list')
            <ul class="space-y-2">
                @foreach ($g['items'] as $item)
                    @php [$label, $rest] = $split($item); @endphp
                    <li class="flex items-start gap-2.5"><x-icon name="check" class="mt-1 size-4 shrink-0 text-gold-800" /><span>@if ($label)<strong class="font-semibold text-steel-950">{{ $label }}</strong> @endif{{ $rest }}</span></li>
                @endforeach
            </ul>
        @else
            @php [$label, $rest] = $split($g['text']); @endphp
            <p>@if ($label)<strong class="font-semibold text-steel-950">{{ $label }}</strong> @endif{{ $rest }}</p>
        @endif
    @endforeach
</div>
