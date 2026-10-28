@props(['text'])
{{-- Plain-text body → paragraphs (blank-line separated). No HTML is rendered. --}}
<div {{ $attributes->merge(['class' => 'prose-copy']) }}>
    @foreach (\App\Support\Ui::paragraphs($text) as $p)
        <p>{{ $p }}</p>
    @endforeach
</div>
