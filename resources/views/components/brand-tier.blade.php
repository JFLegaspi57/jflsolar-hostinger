@props(['slug'])
@php $tier = \App\Support\BrandArticles::TIERS[$slug] ?? null; @endphp
{{-- Tier classification of an inverter / solar panel brand, shown at the end of the article's "Who is" section. --}}
    @if ($tier)
        {{-- Tier classification (BloombergNEF / S&P Global), shown for the inverter and solar panel brands. --}}
        <section aria-labelledby="tier" class="ba-tier mt-6 {{ $tier[1] ? 'is-tier1' : '' }}">
            <div class="ba-tier-head">
                <span class="ba-tier-badge">{{ $tier[4] ?? ($tier[1] ? 'Tier 1' : 'Not Tier 1') }}</span>
                <h3 id="tier">Tier classification: {{ $tier[0] }}</h3>
            </div>
            <ul class="dots mt-3">
                @foreach ($tier[2] as [$listing, $period])<li>{{ $listing }} <span class="text-steel-600">({{ $period }})</span></li>@endforeach
            </ul>
            <p class="mt-3 text-sm text-steel-700"><strong>What “Tier 1” means:</strong> BloombergNEF and S&amp;P Global list a manufacturer as Tier 1 when its products are widely trusted by banks and investors for large, financed projects. It is a sign of a stable, established company, not a direct grade of product quality, and smaller brands that are not listed can still make reliable products.</p>
            @unless ($tier[1])
                <p class="mt-2 text-sm text-steel-700"><strong>Tier 2 and Tier 3:</strong> BloombergNEF publishes only a Tier 1 list. In the industry, “Tier 2” usually means an established manufacturer with its own production that is smaller or less widely financed than Tier 1 makers, and “Tier 3” means a newer company that mainly assembles products from bought-in parts. This classification is our assessment from public information, not an official ranking.</p>
            @endunless
            <p class="mt-2 text-xs text-steel-600">Checked November 2026. Sources: {!! collect($tier[3])->map(fn ($url, $label) => '<a href="'.e($url).'" target="_blank" rel="noopener" class="inline">'.e($label).'</a>')->implode('; ') !!}.</p>
        </section>
    @endif
