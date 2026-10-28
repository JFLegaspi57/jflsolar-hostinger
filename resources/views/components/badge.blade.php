@props(['tone' => 'neutral'])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border max-w-full px-2.5 py-0.5 text-xs font-semibold leading-5 '.\App\Support\Ui::BADGE_TONES[$tone]]) }}>{{ $slot }}</span>
