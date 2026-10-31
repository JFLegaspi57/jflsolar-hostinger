<x-site-layout title="Unsubscribe" path="/newsletter/unsubscribe" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <div class="mx-auto max-w-xl">
            @if ($done === true)
                <h1 class="text-3xl font-extrabold text-steel-950">You’re unsubscribed</h1>
                <p class="mt-3 text-lg text-steel-700">{{ $email }} will no longer receive JFL Solar promos and news.</p>
            @elseif ($done === false)
                <h1 class="text-3xl font-extrabold text-steel-950">Link not recognised</h1>
                <p class="mt-3 text-lg text-steel-700">This unsubscribe link is not valid. Please contact us and we’ll remove you from the list.</p>
            @else
                <h1 class="text-3xl font-extrabold text-steel-950">Unsubscribe from our emails?</h1>
                <p class="mt-3 text-lg text-steel-700">You’ll stop receiving JFL Solar promos and news.</p>
                <form method="post" action="/newsletter/unsubscribe/{{ $token }}" class="mt-6">
                    @csrf
                    <button type="submit" class="{{ \App\Support\Ui::button('primary', 'lg') }}">Unsubscribe</button>
                </form>
            @endif
        </div>
    </x-container>
</x-site-layout>
