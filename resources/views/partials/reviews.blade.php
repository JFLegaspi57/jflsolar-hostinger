{{-- Three random customer recommendations from the JFL Solar Facebook page, shown above the footer on every public page. --}}
@php \App\Support\FacebookReviews::refreshIfDue(); $reviews = \App\Support\Reviews::random(3); @endphp
@if ($reviews)
    <section aria-labelledby="reviews-title" class="border-t border-steel-200 bg-steel-50 py-16 sm:py-20">
        <x-container>
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <x-section-heading id="reviews-title" eyebrow="Customer reviews" title="What people say about us" />
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                    <a href="{{ \App\Support\Reviews::SOURCE_URL }}" target="_blank" rel="noopener" class="inline-flex min-h-11 shrink-0 items-center gap-2 font-semibold text-steel-900 underline-offset-4 hover:underline">
                        <x-icon name="facebook" class="size-5" /> Read all reviews<span class="sr-only"> on Facebook (opens in a new tab)</span>
                    </a>
                    <a href="{{ \App\Support\Reviews::SOURCE_URL }}" target="_blank" rel="noopener" class="{{ \App\Support\Ui::button('primary', 'md') }}">
                        Write a review<span class="sr-only"> on Facebook (opens in a new tab)</span>
                    </a>
                </div>
            </div>
            <ul class="mt-10 grid gap-6 md:grid-cols-3" data-reviews>
                @foreach ($reviews as $r)
                    <li class="flex h-full flex-col rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6">
                        <span aria-hidden="true" class="font-display text-5xl text-gold-500" style="height: 1.75rem; line-height: 1">&ldquo;</span>
                        <blockquote class="mt-2 mb-6 text-steel-800 leading-relaxed" data-review-text>{{ $r['text'] }}</blockquote>
                        <div class="mt-auto flex items-center gap-3 border-t border-steel-200 pt-4">
                            <span aria-hidden="true" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-gold-100 font-bold text-gold-800" data-review-initial>{{ mb_substr($r['name'], 0, 1) }}</span>
                            <div>
                                <p class="font-semibold text-steel-950" data-review-name>{{ $r['name'] }}</p>
                                <p class="text-sm text-steel-600">Recommends JFL Solar on Facebook</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-container>
    </section>
    {{-- The server picks 3 at random (works without JavaScript). The browser then picks a fresh set every time
         the page is shown, including the Back button, and never repeats the set the visitor just saw. --}}
    <script type="application/json" id="reviews-data">@json(\App\Support\Reviews::all())</script>
    <script>
        (function () {
            var list = document.querySelector('[data-reviews]'), data;
            try { data = JSON.parse(document.getElementById('reviews-data').textContent); } catch (e) { return; }
            if (!list || !data || data.length < 4) return;
            var cards = list.children, KEY = 'jfl-reviews-last';
            function shown() { return [].map.call(cards, function (c) { return c.querySelector('[data-review-name]').textContent; }); }
            function last() { try { return JSON.parse(sessionStorage.getItem(KEY) || '[]'); } catch (e) { return []; } }
            function remember() { try { sessionStorage.setItem(KEY, JSON.stringify(shown())); } catch (e) {} }
            function reshuffle() {
                var avoid = last().concat(shown()), pool = data.filter(function (r) { return avoid.indexOf(r.name) < 0; });
                if (pool.length < cards.length) pool = data.slice();
                for (var i = 0; i < cards.length; i++) {
                    var r = pool.splice(Math.floor(Math.random() * pool.length), 1)[0];
                    cards[i].querySelector('[data-review-text]').textContent = r.text;
                    cards[i].querySelector('[data-review-name]').textContent = r.name;
                    cards[i].querySelector('[data-review-initial]').textContent = r.name.charAt(0);
                }
            }
            // Every time the page is shown (new visit, refresh or Back button): a fresh set, never the last one seen.
            window.addEventListener('pageshow', function () { reshuffle(); remember(); });
        })();
    </script>
@endif
