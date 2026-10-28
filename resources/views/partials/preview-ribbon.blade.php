@unless (\App\Support\Site::isProduction())
    {{-- Visible notice on non-production sites. --}}
    <div role="region" aria-label="Proposal preview notice" class="bg-steel-950 text-center text-xs leading-5 text-steel-100">
        <p class="mx-auto max-w-[76rem] px-4 py-1.5">
            <strong class="font-semibold text-gold-400">Proposal preview.</strong>
            <span class="sm:hidden">Sample content is labelled.</span>
            <span class="hidden sm:inline">Sample packages, products and projects are labelled; claims marked “Awaiting confirmation” are not yet approved. Not indexed by search engines.</span>
        </p>
    </div>
@endunless
