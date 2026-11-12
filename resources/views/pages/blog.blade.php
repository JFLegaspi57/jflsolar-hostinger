@php
    use App\Support\Blog;
    use App\Support\Site;
@endphp
<x-site-layout title="Blog by JF Legaspi" path="/blog" :reviews-above-footer="false" description="The blog of JF Legaspi of JFL Solar: notes, lessons and stories about solar PV systems, projects and the business.">
    <x-page-header eyebrow="{{ Blog::AUTHOR }}" title="Blog" intro="Notes, lessons and stories from the field: solar PV systems, projects and running JFL Solar." />
    <x-container class="py-10 sm:py-14">
        @if ($posts->isNotEmpty())
            <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <li class="flex">
                        <article class="group relative flex w-full flex-col overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-white">
                            @if ($post->cover)
                                <div class="aspect-[3/2] overflow-hidden bg-steel-100" oncontextmenu="return false" style="-webkit-touch-callout: none; user-select: none">
                                    <x-media-image :asset="$post->cover" sizes="(min-width: 1024px) 380px, (min-width: 768px) 50vw, 100vw" class="transition-transform duration-300 group-hover:scale-[1.02]" style="object-position: 50% 0%" draggable="false" />
                                </div>
                            @endif
                            <div class="flex flex-1 flex-col p-5">
                                <p class="text-xs font-semibold tracking-wide text-steel-600 uppercase"><time datetime="{{ $post->published_at?->timezone('Asia/Manila')->toDateString() }}">{{ Blog::date($post) }}</time></p>
                                <h2 class="mt-2 text-lg font-bold text-steel-950">
                                    <a href="/blog/{{ $post->slug }}" class="after:absolute after:inset-0 hover:underline">{{ $post->title }}</a>
                                </h2>
                                @if ($post->excerpt)<p class="mt-1.5 text-[0.95rem] text-steel-600">{{ $post->excerpt }}</p>@endif
                                @if ($post->is_sample && ! Site::isProduction())<div class="mt-3"><x-sample-badge label="Sample post" /></div>@endif
                                <p class="mt-auto pt-4 text-sm font-semibold text-steel-900">Read more <span aria-hidden="true">→</span></p>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 p-10 text-center">
                <p class="text-lg font-semibold text-steel-900">The first post is on the way.</p>
                <p class="mt-2 text-steel-600">In the meantime, read <a href="/about" class="font-semibold underline">about JFL Solar</a> or our <a href="/hybrid-pv-system-guide" class="font-semibold underline">guide to hybrid solar PV systems</a>.</p>
            </div>
        @endif
    </x-container>
</x-site-layout>
