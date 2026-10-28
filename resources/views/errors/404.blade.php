<x-site-layout title="Page not found" :path="'/'.request()->path()" noindex>
    <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center px-4 py-20">
        <p class="font-display text-sm font-bold tracking-wide text-gold-800 uppercase">Error 404</p>
        <h1 class="mt-2 text-4xl font-extrabold text-steel-950">We couldn’t find that page.</h1>
        <p class="mt-4 text-lg text-steel-600">It may have moved, or the package or product may no longer be listed.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="/" class="{{ \App\Support\Ui::button() }}">Go to the home page</a>
            <a href="/contact" class="{{ \App\Support\Ui::button('secondary') }}">Contact us</a>
        </div>
        <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-steel-800">
            @foreach (['/packages' => 'Packages', '/products' => 'Products', '/services' => 'Solar Services'] as $href => $label)
                <li><a href="{{ $href }}" class="inline-flex min-h-11 items-center font-semibold underline">{{ $label }}</a></li>
            @endforeach
        </ul>
    </div>
</x-site-layout>
