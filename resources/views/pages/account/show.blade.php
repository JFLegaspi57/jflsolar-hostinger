@php
    use App\Support\Pricing;
    use App\Support\Ui;
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900 aria-[invalid=true]:border-danger-700 aria-[invalid=true]:bg-danger-50/40';
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8';
    $err = fn ($k) => $errors->first($k) ?: null;
@endphp
<x-site-layout title="Your account" path="/account" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold tracking-wide text-steel-600 uppercase">Your account</p>
                <h1 class="mt-1 text-3xl font-extrabold text-steel-950 sm:text-4xl">Hello, {{ $member->name }}</h1>
                <p class="mt-1 text-steel-700">{{ $member->email }}</p>
            </div>
            <form method="post" action="/logout">@csrf<button type="submit" class="{{ Ui::button('secondary') }}">Sign out</button></form>
        </div>
        <div class="mt-6 space-y-4">@include('partials.account-flash')</div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_22rem]">
            <section aria-labelledby="orders-h" class="{{ $card }} self-start">
                <h2 id="orders-h" class="text-xl font-bold text-steel-950">Your orders</h2>
                @if ($orders->isEmpty())
                    <p class="mt-3 text-steel-700">No orders yet. Orders you place while signed in appear here.</p>
                    <p class="mt-4"><a href="/products" class="{{ Ui::button('primary') }}">Browse products</a></p>
                @else
                    <ul class="mt-3 divide-y divide-steel-200">
                        @foreach ($orders as $o)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div>
                                    <a href="{{ $o->publicPath() }}" class="font-mono font-semibold text-steel-950 underline">{{ $o->reference }}</a>
                                    <p class="text-sm text-steel-600">{{ $o->created_at->copy()->setTimezone('Asia/Manila')->format('M j, Y') }} · {{ $o->items_count }} {{ $o->items_count === 1 ? 'item' : 'items' }} · {{ $o->statusLabel() }}</p>
                                </div>
                                <span class="font-semibold text-steel-950 tabular">{{ Pricing::formatPhp($o->total_php) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <div class="space-y-8">
                <section id="details" aria-labelledby="details-h" class="{{ $card }}">
                    <h2 id="details-h" class="text-xl font-bold text-steel-950">Your details</h2>
                    <form method="post" action="/account" class="mt-4 space-y-4">
                        @csrf
                        <x-form-field id="a-name" label="Name" :error="$err('name')">
                            <input id="a-name" name="name" type="text" autocomplete="name" value="{{ old('name', $member->name) }}" maxlength="100" required @if ($err('name')) aria-invalid="true" aria-describedby="a-name-error" @endif class="{{ $input }}">
                        </x-form-field>
                        <x-form-field id="a-phone" label="Mobile or phone" :error="$err('phone')">
                            <input id="a-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" value="{{ old('phone', $member->phone) }}" maxlength="30" @if ($err('phone')) aria-invalid="true" aria-describedby="a-phone-error" @endif class="{{ $input }} tabular">
                        </x-form-field>
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" name="newsletter" value="1" @checked($subscribed) class="mt-1 size-5 shrink-0 rounded border-steel-500">
                            <span class="text-steel-800">Email me JFL Solar promos and news</span>
                        </label>
                        <button type="submit" class="{{ Ui::button('primary') }}">Save details</button>
                    </form>
                </section>

                <section id="password" aria-labelledby="password-h" class="{{ $card }}">
                    <h2 id="password-h" class="text-xl font-bold text-steel-950">Change password</h2>
                    <form method="post" action="/account/password" class="mt-4 space-y-4">
                        @csrf
                        <x-form-field id="a-current" label="Current password" :error="$err('current_password')">
                            <input id="a-current" name="current_password" type="password" autocomplete="current-password" maxlength="200" required @if ($err('current_password')) aria-invalid="true" aria-describedby="a-current-error" @endif class="{{ $input }}">
                        </x-form-field>
                        <x-form-field id="a-new" label="New password" hint="At least 10 characters." :error="$err('password')">
                            <input id="a-new" name="password" type="password" autocomplete="new-password" maxlength="200" required aria-describedby="a-new-hint{{ $err('password') ? ' a-new-error' : '' }}" @if ($err('password')) aria-invalid="true" @endif class="{{ $input }}">
                        </x-form-field>
                        <x-form-field id="a-new2" label="Type it again">
                            <input id="a-new2" name="password_confirmation" type="password" autocomplete="new-password" maxlength="200" required class="{{ $input }}">
                        </x-form-field>
                        <button type="submit" class="{{ Ui::button('secondary') }}">Change password</button>
                    </form>
                </section>

                <section id="delete" aria-labelledby="delete-h" class="{{ $card }}">
                    <h2 id="delete-h" class="text-xl font-bold text-steel-950">Delete account</h2>
                    <p class="mt-2 text-sm text-steel-700">Your account and details are deleted and you leave our promo list. Orders stay in our sales records. This can’t be undone.</p>
                    <form method="post" action="/account/delete" class="mt-4 space-y-4">
                        @csrf
                        <x-form-field id="a-del" label="Your password" :error="$err('delete_password')">
                            <input id="a-del" name="password" type="password" autocomplete="current-password" maxlength="200" required @if ($err('delete_password')) aria-invalid="true" aria-describedby="a-del-error" @endif class="{{ $input }}">
                        </x-form-field>
                        <button type="submit" class="{{ Ui::button('secondary') }}">Delete my account</button>
                    </form>
                </section>
            </div>
        </div>
    </x-container>
</x-site-layout>
