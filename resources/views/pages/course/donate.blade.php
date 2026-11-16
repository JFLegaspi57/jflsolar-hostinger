@php $gcash = \App\Support\Course::gcash(); $tl = $tl ?? false; @endphp
{{-- GCash donation box: the course is free; donations help keep it going. --}}
@if ($gcash)
    <section aria-labelledby="donate-h" class="flex flex-wrap items-center gap-5 rounded-[var(--radius-panel)] border border-[#b9d3f5] bg-[#eef5ff] p-5 sm:p-6 {{ $class ?? '' }}">
        @if ($gcash['qr'])
            <img src="{{ $gcash['qr'] }}" alt="GCash QR code for donations to JFL Solar" width="180" height="180" class="shrink-0 rounded-[var(--radius-control)] border border-[#b9d3f5] bg-white p-1" style="width: 180px; height: auto">
        @endif
        <div class="min-w-0 flex-1" style="min-width: 15rem">
            <h2 id="donate-h" class="text-xl font-bold text-steel-950">{{ $tl ? 'Libre ang kursong ito. Suportahan kami sa GCash' : 'This course is free. Support it with GCash' }}</h2>
            <p class="mt-2 text-steel-800">{{ $tl ? 'Kung nakakatulong sa iyo ang mga aralin, maaari kang magbigay ng kahit anong halaga sa GCash. Nakakatulong ito para makagawa pa kami ng mas maraming aralin.' : 'If the lessons help you, you can donate any amount by GCash. It helps us keep making new lessons.' }}</p>
            @if ($gcash['number'])
                <p class="mt-3 font-semibold text-steel-950">GCash: <span class="tabular">{{ $gcash['number'] }}</span>@if ($gcash['name']) <span class="font-normal text-steel-700">· {{ $gcash['name'] }}</span>@endif</p>
            @endif
            <p class="mt-1 text-sm text-steel-600">{{ $tl ? 'I-scan ang QR code gamit ang GCash app. Salamat sa iyong suporta!' : 'Scan the QR code with your GCash app. Thank you for your support!' }}</p>
        </div>
    </section>
@endif
