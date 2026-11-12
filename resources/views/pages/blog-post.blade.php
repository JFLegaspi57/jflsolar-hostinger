@php
    use App\Support\Blog;
    use App\Support\Site;
    use App\Support\Ui;
    $description = $post->excerpt ?: \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $post->body), 155);
    $more = $post->photos->reject(fn ($m) => $post->cover && $m->id === $post->cover->id);
    $jsonLd = [
        '@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post->title, 'description' => $description,
        'datePublished' => $post->published_at?->toAtomString(), 'dateModified' => $post->updated_at?->toAtomString(),
        'author' => ['@type' => 'Person', 'name' => Blog::AUTHOR], 'publisher' => ['@type' => 'Organization', 'name' => Site::NAME],
        'mainEntityOfPage' => Site::url('/blog/'.$post->slug),
    ];
@endphp
<x-site-layout :title="$post->title" :path="'/blog/'.$post->slug" :description="$description" :noindex="$post->is_sample" :reviews-above-footer="false">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode(array_filter($jsonLd), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>
    <x-page-header eyebrow="Blog" :title="$post->title" />
    <x-container class="py-10 sm:py-14">
        <article class="max-w-3xl">
            <p class="text-sm font-semibold text-steel-600">By <a href="/about" class="text-steel-900 underline">{{ Blog::AUTHOR }}</a> · <time datetime="{{ $post->published_at?->timezone('Asia/Manila')->toDateString() }}">{{ Blog::date($post) }}</time></p>
            @if ($post->is_sample && ! Site::isProduction())<div class="mt-3"><x-sample-badge label="Sample post" /></div>@endif
            @if ($post->cover)
                <figure class="mt-6">
                    {{-- Tall (portrait) photos are capped in height so they don't fill the whole screen. --}}
                    <div class="flex justify-center overflow-hidden rounded-[var(--radius-panel)] bg-steel-100">
                        <x-media-image :asset="$post->cover" sizes="(min-width: 768px) 768px, 100vw" priority fit="contain" style="width: auto; height: auto; max-width: 100%; max-height: 36rem" />
                    </div>
                    @if ($post->cover->caption)<figcaption class="mt-2 text-sm text-steel-600">{{ $post->cover->caption }}</figcaption>@endif
                </figure>
            @endif
            <x-paragraphs :text="$post->body" class="mt-8 text-lg leading-relaxed text-steel-800" />
            @if ($more->isNotEmpty())
                {{-- Other photos as a gallery; each opens full size. --}}
                <ul class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3" aria-label="Photos">
                    @foreach ($more as $img)
                        <li>
                            <figure>
                                <a href="{{ $img->url($img->bestWidth(2000)) }}" target="_blank" rel="noopener" class="relative block overflow-hidden rounded-[var(--radius-panel)] bg-steel-100" style="aspect-ratio: 3 / 4">
                                    <x-media-image :asset="$img" sizes="(min-width: 640px) 250px, 50vw" style="position: absolute; inset: 0; object-position: 50% 15%" />
                                    <span class="sr-only">Open photo full size (opens in a new tab)</span>
                                </a>
                                @if ($img->caption)<figcaption class="mt-2 text-sm text-steel-600">{{ $img->caption }}</figcaption>@endif
                            </figure>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-12 flex flex-col gap-2 border-t border-steel-200 sm:flex-row" style="padding-top: 1.5rem">
                <a href="/blog" class="{{ Ui::button('secondary', 'lg') }}"><span aria-hidden="true">←</span> All posts</a>
                <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Request a Quote</a>
            </div>
        </article>
    </x-container>
</x-site-layout>
