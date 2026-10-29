{{-- Three random customer recommendations from the JFL Solar Facebook page, shown above the footer on every public page. --}}
@php $reviews = \App\Support\Reviews::random(3); @endphp
@if ($reviews)
    <section aria-labelledby="reviews-title" class="border-t border-steel-200 bg-steel-50 py-16 sm:py-20">
        <x-container>
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <x-section-heading id="reviews-title" eyebrow="Customer reviews" title="What people say about JFL Solar" />
                <a href="{{ \App\Support\Reviews::SOURCE_URL }}" target="_blank" rel="noopener" class="inline-flex min-h-11 shrink-0 items-center gap-2 font-semibold text-steel-900 underline-offset-4 hover:underline">
                    <x-icon name="facebook" class="size-5" /> Read all reviews on Facebook<span class="sr-only"> (opens in a new tab)</span>
                </a>
            </div>
            <ul class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($reviews as $r)
                    <li class="flex h-full flex-col rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6">
                        <span aria-hidden="true" class="font-display text-5xl text-gold-500" style="height: 1.75rem; line-height: 1">&ldquo;</span>
                        <blockquote class="mt-2 mb-6 text-steel-800 leading-relaxed">{{ $r['text'] }}</blockquote>
                        <div class="mt-auto flex items-center gap-3 border-t border-steel-200 pt-4">
                            <span aria-hidden="true" class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-gold-100 font-bold text-gold-800">{{ mb_substr($r['name'], 0, 1) }}</span>
                            <div>
                                <p class="font-semibold text-steel-950">{{ $r['name'] }}</p>
                                <p class="text-sm text-steel-600">Recommends JFL Solar on Facebook</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-container>
    </section>
@endif
