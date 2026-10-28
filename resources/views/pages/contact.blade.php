@php
    use App\Support\Inquiries;
    use App\Support\Labels;
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900 aria-[invalid=true]:border-danger-700 aria-[invalid=true]:bg-danger-50/40';
    $v = $values;
    $e = $errors->getMessages();
    $err = fn ($k) => $e[$k][0] ?? null;
    $describe = fn (...$ids) => implode(' ', array_filter($ids)) ?: null;
    $summary = session('inquiry_message');
    $billShown = in_array($v['inquiry_type'], Labels::BILL_RELEVANT, true);
@endphp
<x-site-layout title="Contact and Request a Quote" path="/contact" description="Request a quotation for solar installation, consultancy, packages or equipment. Call, message on WhatsApp or Viber, or send an inquiry to JFL Solar.">
    <x-page-header eyebrow="Contact" title="Request a quote" :intro="trim($settings->service_area.' '.($settings->service_area_note ?? ''))" />
    <x-container class="grid gap-12 py-10 sm:py-14 lg:grid-cols-[1.25fr_1fr]">
        <section id="quote" aria-labelledby="quote-title" class="scroll-mt-28">
            <h2 id="quote-title" class="text-2xl font-bold text-steel-950">Send an inquiry</h2>
            @if (session('inquiry_sent'))
                <div tabindex="-1" role="status" data-focus-on-load class="mt-6 rounded-[var(--radius-panel)] border border-[#b7e2c6] bg-ok-50 p-6 focus:outline-none">
                    <h3 class="flex items-center gap-2 text-xl font-bold text-ok-700"><x-icon name="check" class="size-6" /> Thank you — your inquiry was received.</h3>
                    <p class="mt-2 text-steel-800">We will reply using the contact details you gave. If it is urgent, call or message us.</p>
                    <p class="mt-4"><a href="/contact" class="font-semibold text-steel-900 underline">Send another inquiry</a></p>
                </div>
            @else
                <p class="mt-1 mb-6 text-steel-600">Fields marked required must be filled in. Everything else is optional.</p>
                @if ($stale)
                    <p role="status" class="mb-4 rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 px-4 py-3 text-warn-800">The item you selected is no longer available. You can still send us a general inquiry.</p>
                @endif

                <form method="post" action="/contact" novalidate class="space-y-5" data-inquiry-form>
                    @csrf
                    @if ($summary)
                        <div tabindex="-1" role="alert" data-focus-on-load class="rounded-[var(--radius-control)] border-2 border-danger-700 bg-danger-50 p-4 focus:outline-none">
                            <p class="font-bold text-danger-700">{{ $summary }}</p>
                            @if ($e)
                                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                                    @foreach (Inquiries::FIELD_TARGET as $k => $target)
                                        @if ($err($k))<li><a href="#{{ $target }}" class="text-danger-700 underline">{{ $err($k) }}</a></li>@endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    <input type="hidden" name="token" value="{{ $token }}">
                    {{-- Honeypot: hidden from people and assistive technology --}}
                    <div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden">
                        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label>
                    </div>

                    @if ($v['item_slug'])
                        <div class="rounded-[var(--radius-control)] border border-steel-300 bg-steel-50 p-3" role="group" aria-labelledby="item-label" data-item-chip>
                            <input type="hidden" name="item_type" value="{{ $v['item_type'] }}">
                            <input type="hidden" name="item_slug" value="{{ $v['item_slug'] }}">
                            <input type="hidden" name="item_name" value="{{ $v['item_name'] }}">
                            <p id="item-label" class="text-xs font-semibold tracking-wide text-steel-600 uppercase">Asking about this {{ $v['item_type'] === 'package' ? 'package' : 'product' }}</p>
                            <div class="mt-1 flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold text-steel-950">{{ $v['item_name'] }}</p>
                                <button id="item-remove" type="button" data-remove-item class="inline-flex min-h-11 items-center gap-1 rounded px-2 text-sm font-semibold text-steel-800 underline hover:text-steel-950">
                                    <x-icon name="x" class="size-4" /> Remove<span class="sr-only"> selected item</span>
                                </button>
                            </div>
                        </div>
                    @elseif ($err('item'))
                        <p id="item-remove" tabindex="-1" class="text-sm font-semibold text-danger-700">{{ $err('item') }}</p>
                    @endif

                    <x-form-field id="f-name" label="Your name" :error="$err('name')" required>
                        <input id="f-name" name="name" autocomplete="name" value="{{ $v['name'] }}" maxlength="{{ Inquiries::LIMITS['name'] }}" required
                            @if ($err('name')) aria-invalid="true" aria-describedby="f-name-error" @endif class="{{ $input }}">
                    </x-form-field>

                    <fieldset aria-describedby="{{ $describe('contact-hint', $err('contact') ? 'contact-error' : null) }}" class="space-y-4">
                        <legend class="text-base font-semibold text-steel-950">How can we reach you?</legend>
                        <p id="contact-hint" class="-mt-2 text-sm text-steel-600">Give at least one: phone or email.</p>
                        @if ($err('contact'))
                            <p id="contact-error" class="flex items-start gap-1.5 text-sm font-semibold text-danger-700"><x-icon name="x" class="mt-0.5 size-4" /> {{ $err('contact') }}</p>
                        @endif
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-form-field id="f-phone" label="Mobile or phone" :error="$err('phone')">
                                <input id="f-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" value="{{ $v['phone'] }}" maxlength="{{ Inquiries::LIMITS['phone'] }}"
                                    @if ($err('phone') || $err('contact')) aria-invalid="true" @endif
                                    @if ($d = $describe($err('phone') ? 'f-phone-error' : null, $err('contact') ? 'contact-error' : null)) aria-describedby="{{ $d }}" @endif
                                    class="{{ $input }} tabular">
                            </x-form-field>
                            <x-form-field id="f-email" label="Email" :error="$err('email')">
                                <input id="f-email" name="email" type="email" autocomplete="email" spellcheck="false" value="{{ $v['email'] }}" maxlength="{{ Inquiries::LIMITS['email'] }}"
                                    @if ($err('email') || $err('contact')) aria-invalid="true" @endif
                                    @if ($d = $describe($err('email') ? 'f-email-error' : null, $err('contact') ? 'contact-error' : null)) aria-describedby="{{ $d }}" @endif
                                    class="{{ $input }}">
                            </x-form-field>
                        </div>
                    </fieldset>

                    <x-form-field id="f-type" label="What is your inquiry about?" :error="$err('inquiry_type')" required>
                        <select id="f-type" name="inquiry_type" required data-inquiry-type @if ($err('inquiry_type')) aria-invalid="true" aria-describedby="f-type-error" @endif class="{{ $input }}">
                            <option value="">Choose one…</option>
                            @foreach (Labels::INQUIRY_TYPES as $value => $label)<option value="{{ $value }}" @selected($v['inquiry_type'] === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </x-form-field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form-field id="f-location" label="Location" hint="City or province (optional)" :error="$err('location')">
                            <input id="f-location" name="location" autocomplete="address-level2" value="{{ $v['location'] }}" maxlength="{{ Inquiries::LIMITS['location'] }}"
                                aria-describedby="{{ $describe('f-location-hint', $err('location') ? 'f-location-error' : null) }}" class="{{ $input }}">
                        </x-form-field>
                        <div data-bill-field data-relevant="{{ implode(',', Labels::BILL_RELEVANT) }}" @unless ($billShown) hidden @endunless>
                            <x-form-field id="f-bill" label="Average monthly electricity bill" hint="Optional — helps us size a system" :error="$err('bill_range')">
                                <select id="f-bill" name="bill_range" @if ($err('bill_range')) aria-invalid="true" @endif aria-describedby="{{ $describe('f-bill-hint', $err('bill_range') ? 'f-bill-error' : null) }}" class="{{ $input }}">
                                    @foreach (Labels::BILL_RANGES as $value => $label)<option value="{{ $value }}" @selected($v['bill_range'] === $value)>{{ $label }}</option>@endforeach
                                </select>
                            </x-form-field>
                        </div>
                    </div>

                    <x-form-field id="f-message" label="Message" hint="Your property, system or equipment needs, and any timing." :error="$err('message')" required>
                        <textarea id="f-message" name="message" rows="5" maxlength="{{ Inquiries::LIMITS['message'] }}" required data-count="f-message-count"
                            @if ($err('message')) aria-invalid="true" @endif aria-describedby="{{ $describe('f-message-hint', $err('message') ? 'f-message-error' : null, 'f-message-count') }}"
                            class="{{ $input }} py-3">{{ $v['message'] }}</textarea>
                        <p id="f-message-count" class="mt-1 text-right text-xs text-steel-600 tabular">{{ mb_strlen($v['message']) }} / {{ Inquiries::LIMITS['message'] }}</p>
                    </x-form-field>

                    <p class="text-sm text-steel-600">We use your details only to reply to this inquiry. See our <a href="/privacy" class="font-semibold text-steel-900 underline">privacy notice</a>.</p>

                    <button type="submit" data-pending-label="Sending…" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-[var(--radius-control)] bg-gold-500 px-6 text-base font-semibold text-steel-950 hover:bg-gold-400 disabled:cursor-wait disabled:opacity-70 sm:w-auto">Send inquiry</button>
                </form>
            @endif
        </section>

        <aside aria-labelledby="direct-title" class="space-y-8">
            <div>
                <h2 id="direct-title" class="text-2xl font-bold text-steel-950">Contact us directly</h2>
                <p class="mt-1 mb-5 text-steel-600">Prefer to talk? Call or message us.</p>
                <x-contact-routes :settings="$settings" />
            </div>
            <div>
                <h2 class="mb-4 text-xl font-bold text-steel-950">Office and warehouse</h2>
                <x-addresses :settings="$settings" />
            </div>
        </aside>
    </x-container>
</x-site-layout>
