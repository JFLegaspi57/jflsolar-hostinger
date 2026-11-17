@php
    $gcash = \App\Support\Course::gcash();
    $tl = $tl ?? false;
    $donor = \App\Support\Members::current();
    $donated = \App\Support\Course::hasDonated($donor);
@endphp
{{-- GCash donation box: the course is free; donations help keep it going. Once a member reports
     a donation (with the GCash reference number), the box no longer shows for them. --}}
@if ($gcash && $donated && ! ($inLesson ?? false))
    <p class="rounded-[var(--radius-panel)] border border-[#b9d3f5] bg-[#eef5ff] p-4 font-semibold text-steel-900 {{ $class ?? '' }}">{{ $tl ? 'Salamat sa iyong donasyon! Nakakatulong ito para makagawa pa kami ng mga bagong aralin.' : 'Thank you for your donation! It helps us keep making new lessons.' }}</p>
@elseif ($gcash && ! $donated)
    <section aria-labelledby="donate-h" class="flex flex-wrap items-center gap-5 rounded-[var(--radius-panel)] border border-[#b9d3f5] bg-[#eef5ff] p-5 sm:p-6 {{ $class ?? '' }}">
        @if ($gcash['qr'])
            <img src="{{ $gcash['qr'] }}" alt="GCash QR code for donations to JFL Solar" width="200" height="200" class="shrink-0 rounded-[var(--radius-control)] border border-[#b9d3f5] bg-white p-1" style="width: 200px; height: auto">
        @endif
        <div class="min-w-0 flex-1" style="min-width: 15rem">
            <h2 id="donate-h" class="text-xl font-bold text-steel-950">{{ $tl ? 'Libre ang kursong ito. Suportahan kami sa GCash' : 'This course is free. Support it with GCash' }}</h2>
            <p class="mt-2 text-steel-800">{{ $tl ? 'Kung nakakatulong sa iyo ang mga aralin, maaari kang magbigay ng kahit anong halaga sa GCash. I-scan ang QR code gamit ang GCash app.' : 'If the lessons help you, you can donate any amount by GCash. Scan the QR code with your GCash app.' }}</p>
            @if ($gcash['number'])
                <p class="mt-2 font-semibold text-steel-950">GCash: <span class="tabular">{{ $gcash['number'] }}</span>@if ($gcash['name']) <span class="font-normal text-steel-700">· {{ $gcash['name'] }}</span>@endif</p>
            @endif
            @if ($donor)
                <form method="post" action="/courses/donated" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <label class="grid gap-1 text-sm font-semibold text-steel-900">{{ $tl ? 'GCash reference no.' : 'GCash reference no.' }}
                        <input name="reference" required inputmode="numeric" autocomplete="off" placeholder="e.g. 1234 567 890123" class="rounded-[var(--radius-control)] border border-steel-300 bg-white px-3 py-2 font-normal" style="width: 13rem">
                    </label>
                    <label class="grid gap-1 text-sm font-semibold text-steel-900">{{ $tl ? 'Halaga (₱, opsyonal)' : 'Amount (₱, optional)' }}
                        <input name="amount" inputmode="decimal" autocomplete="off" placeholder="2,000" class="rounded-[var(--radius-control)] border border-steel-300 bg-white px-3 py-2 font-normal" style="width: 7rem">
                    </label>
                    <button type="submit" class="{{ \App\Support\Ui::button('primary') }}">{{ $tl ? 'Naipadala ko na' : "I've sent my donation" }}</button>
                </form>
                <p class="mt-2 text-sm text-steel-600">{{ $tl ? 'Pagkatapos magpadala, ilagay ang reference number mula sa GCash receipt. Mawawala na ang box na ito sa iyong mga aralin. Salamat!' : 'After sending, enter the reference number from your GCash receipt and this box will no longer show in your lessons. Thank you!' }}</p>
            @endif
        </div>
    </section>
@endif
