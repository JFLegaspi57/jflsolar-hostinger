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
            <div class="space-y-8 self-start">
            @if ($basket)
                <section id="basket" aria-labelledby="basket-h" class="{{ $card }}">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="basket-h" class="text-xl font-bold text-steel-950">Your basket</h2>
                        @if ($basket['count'])<span class="text-sm text-steel-600">{{ $basket['count'] }} {{ $basket['count'] === 1 ? 'item' : 'items' }}</span>@endif
                    </div>
                    @if (! $basket['lines'])
                        <p class="mt-3 text-steel-700">Your basket is empty.</p>
                        <p class="mt-4"><a href="/products" class="{{ Ui::button('secondary') }}">Browse products</a></p>
                    @else
                        <ul class="mt-3 divide-y divide-steel-200">
                            @foreach ($basket['lines'] as $line)
                                <li class="flex items-start justify-between gap-3 py-3">
                                    <div class="min-w-0">
                                        <a href="/products/{{ $line['product']->slug }}" class="font-semibold text-steel-950 underline-offset-2 hover:underline">{{ $line['product']->name }}</a>
                                        <p class="text-sm text-steel-600 tabular">{{ $line['qty'] }} {{ $line['unit'] }} × {{ Pricing::formatPhp($line['unit_price']) }}</p>
                                    </div>
                                    <span class="font-semibold text-steel-950 tabular">{{ Pricing::formatPhp($line['line_total']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-2 flex justify-between border-t border-steel-300 pt-3 text-lg font-bold text-steel-950"><span>Total</span><span class="tabular">{{ Pricing::formatPhp($basket['total']) }}</span></p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="/checkout" class="{{ Ui::button('primary') }}">Check out</a>
                            <a href="/cart" class="{{ Ui::button('secondary') }}">Edit basket</a>
                        </div>
                    @endif
                </section>
            @endif

            <section aria-labelledby="orders-h" class="{{ $card }}">
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
            </div>

            <div class="space-y-8">
                <section id="profile" aria-labelledby="profile-h" class="{{ $card }}">
                    <h2 id="profile-h" class="text-xl font-bold text-steel-950">Profile</h2>
                    <p class="mt-1 text-sm text-steel-600">Only you and the JFL Solar team can see your photo and links.</p>
                    <form method="post" action="/account/profile" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <div class="flex items-center gap-4">
                            @if ($photo = \App\Support\Members::photoUrl($member))
                                <img src="{{ $photo }}" alt="Your profile photo" width="80" height="80" style="width: 5rem; height: 5rem" class="shrink-0 rounded-full border border-steel-200 object-cover">
                            @else
                                <span style="width: 5rem; height: 5rem" class="inline-flex shrink-0 items-center justify-center rounded-full bg-steel-100 text-steel-600"><x-icon name="user" class="size-10" /></span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <label for="p-photo" class="mb-1 block font-semibold text-steel-950">{{ $member->photo ? 'Change photo' : 'Add a photo' }}</label>
                                <input id="p-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" @if ($err('photo')) aria-invalid="true" aria-describedby="p-photo-error" @endif class="block w-full text-sm text-steel-800">
                                @if ($err('photo'))<p id="p-photo-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $err('photo') }}</p>@endif
                                @if ($member->photo)
                                    <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-steel-800"><input type="checkbox" name="remove_photo" value="1" class="size-4 rounded border-steel-500"> Remove my photo</label>
                                @endif
                            </div>
                        </div>
                        @foreach (\App\Support\Members::SOCIALS as $field => [$label, , $pattern])
                            @php $icon = ['facebook_url' => 'facebook', 'youtube_url' => 'youtube', 'instagram_url' => 'instagram', 'tiktok_url' => 'tiktok'][$field]; @endphp
                            <x-form-field :id="'p-'.$field" :label="$label" :error="$err($field)">
                                <div class="relative">
                                    <span class="absolute text-steel-600" style="left: .75rem; top: 50%; transform: translateY(-50%); pointer-events: none"><x-icon :name="$icon" class="size-5" /></span>
                                    <input id="p-{{ $field }}" name="{{ $field }}" type="text" inputmode="url" autocomplete="url" spellcheck="false" maxlength="300"
                                        value="{{ old($field, $member->{$field}) }}" placeholder="{{ sprintf($pattern, 'yourname') }}" style="padding-left: 2.6rem"
                                        @if ($err($field)) aria-invalid="true" aria-describedby="p-{{ $field }}-error" @endif class="{{ $input }}">
                                </div>
                            </x-form-field>
                        @endforeach
                        <p class="text-sm text-steel-600">Paste the link to your page, or just type @yourname.</p>
                        <button type="submit" class="{{ Ui::button('primary') }}">Save profile</button>
                    </form>
                </section>

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
                    <p class="mt-2 text-sm text-steel-700">Your account and details are deleted and you leave our promo list. Orders stay in our sales records; forum posts stay, shown as “Former member”. This can’t be undone.</p>
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
