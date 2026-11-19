@php
    use App\Support\Course;
    use Illuminate\Support\Carbon;
    $btn = 'inline-flex min-h-11 items-center rounded-[var(--radius-control)] px-4 font-semibold';
    $box = 'mb-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4 sm:p-6';
    $field = 'min-h-11 rounded-[var(--radius-control)] border border-steel-500 px-3';
    $d = fn ($t) => $t ? Carbon::parse($t)->timezone('Asia/Manila')->format('j M Y') : '—';
@endphp
<x-admin.layout title="Online course">
    <x-admin.page-title title="Online course" :intro="Course::TITLE.' — free for every signed-in member, with optional GCash donations.'">
        <x-slot:action><a href="{{ Course::PATH }}" target="_blank" rel="noopener" class="{{ $btn }} border border-steel-400 text-steel-900">View course page</a></x-slot:action>
    </x-admin.page-title>

    @include('admin.course.stats')

    <section class="{{ $box }}" aria-labelledby="certpay-h">
        <h2 id="certpay-h" class="text-lg font-bold text-steel-950">Certificates: GCash payments</h2>
        <p class="mt-1 text-sm text-steel-700">Students with a certificate number see this QR code on their account page, to pay ₱{{ number_format(\App\Support\CourseCertificates::FEE_PHP) }} and unlock the download. Issue numbers, upload certificates and mark payments in Admin → Members → (student).</p>
        @php $cq = \App\Support\CourseCertificates::qrUrl(); $waiting = \App\Support\CourseCertificates::waiting(); @endphp
        @if ($waiting->isNotEmpty())
            <div class="mt-3 rounded border border-gold-500 bg-gold-50 p-3 text-sm"><p class="font-semibold">Payments to check in GCash:</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($waiting as $w)<li><a href="/admin/members/{{ $w->member_id }}#course-h" class="underline">{{ $w->name }}</a> · ref. <strong>{{ $w->gcash_ref }}</strong> · {{ \Illuminate\Support\Carbon::parse($w->ref_at)->timezone('Asia/Manila')->format('j M, g:i A') }} · {{ $w->ctn }}</li>@endforeach</ul></div>
        @endif
        @if ($cq)<img src="{{ $cq }}" alt="" class="mt-3 rounded border border-steel-200" style="width: 160px">@endif
        <form method="post" action="/admin/course/certificate-qr" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <label class="grid gap-1 font-semibold text-steel-900">GCash QR code for certificates <input type="file" name="qr" accept="image/jpeg,image/png,image/webp" class="font-normal" required></label>
            <button type="submit" class="{{ $btn }} bg-steel-900 text-white">Save</button>
        </form>
        @if ($cq)<form method="post" action="/admin/course/certificate-qr" class="mt-2">@csrf<input type="hidden" name="remove" value="1"><button class="text-sm text-danger-700 underline">Remove QR code</button></form>@endif
    </section>

    <section class="{{ $box }}" aria-labelledby="gcash-h">
        <h2 id="gcash-h" class="text-lg font-bold text-steel-950">GCash donations</h2>
        <p class="mt-1 text-sm text-steel-700">Shown on the course page and at the top of every lesson, before the lesson starts.</p>
        @php $gc = Course::gcash(); @endphp
        @if ($gc && $gc['qr'])<img src="{{ $gc['qr'] }}" alt="" class="mt-3 rounded border border-steel-200" style="width: 160px">@endif
        <form method="post" action="/admin/course/gcash" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <label class="grid gap-1 font-semibold text-steel-900">GCash QR code <input type="file" name="qr" accept="image/jpeg,image/png,image/webp" class="font-normal"></label>
            <label class="grid gap-1 font-semibold text-steel-900">GCash number <input name="number" value="{{ $gc['number'] ?? '' }}" placeholder="09XX XXX XXXX" class="{{ $field }} font-normal" style="width: 12rem"></label>
            <label class="grid gap-1 font-semibold text-steel-900">Account name <input name="name" value="{{ $gc['name'] ?? '' }}" placeholder="e.g. J. F. L." class="{{ $field }} font-normal" style="width: 12rem"></label>
            <button type="submit" class="{{ $btn }} bg-steel-900 text-white">Save</button>
        </form>
        @if ($gc && $gc['qr'])<form method="post" action="/admin/course/gcash" class="mt-2">@csrf<input type="hidden" name="remove_qr" value="1"><button class="text-sm text-danger-700 underline">Remove QR code</button></form>@endif
    </section>
    <section class="{{ $box }}" aria-labelledby="don-h">
        <h2 id="don-h" class="text-lg font-bold text-steel-950">Donations reported by members</h2>
        <p class="mt-1 text-sm text-steel-700">When a member taps "I've sent my donation" with the GCash reference number, the GCash box disappears from their lessons. Check the reference in your GCash app, then confirm it. If no money arrived, reject it and the box shows again for that member.</p>
        @if ($donations->isEmpty())
            <p class="mt-3 text-steel-600">No donations reported yet.</p>
        @else
            <div class="mt-3 overflow-x-auto"><table class="w-full text-sm">
                <tr class="text-left text-steel-600"><th class="py-1 pr-3">Date</th><th class="pr-3">Member</th><th class="pr-3">GCash ref.</th><th class="pr-3">Amount</th><th class="pr-3">Status</th><th></th></tr>
                @foreach ($donations as $d)
                    <tr class="border-t border-steel-200">
                        <td class="py-2 pr-3 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($d->created_at)->timezone('Asia/Manila')->format('j M Y, g:i A') }}</td>
                        <td class="pr-3">{{ $d->name }}<br><span class="text-steel-600">{{ $d->email }}</span></td>
                        <td class="pr-3 tabular">{{ $d->reference }}</td>
                        <td class="pr-3 tabular">{{ $d->amount_php !== null ? '₱'.number_format((float) $d->amount_php, 2) : '—' }}</td>
                        <td class="pr-3 font-semibold {{ $d->status === 'confirmed' ? 'text-ok-700' : ($d->status === 'rejected' ? 'text-danger-700' : 'text-warn-800') }}">{{ ucfirst($d->status) }}</td>
                        <td class="whitespace-nowrap">
                            @foreach (['confirmed' => 'Confirm', 'rejected' => 'Reject'] as $st => $lbl)
                                @if ($d->status !== $st)<form method="post" action="/admin/course/donations/{{ $d->id }}" class="inline">@csrf<input type="hidden" name="status" value="{{ $st }}"><button class="mr-2 underline">{{ $lbl }}</button></form>@endif
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </table></div>
        @endif
    </section>
    @unless (Course::FREE)
    <section class="{{ $box }}" aria-labelledby="price-h">
        <h2 id="price-h" class="text-lg font-bold text-steel-950">Membership price (1 year)</h2>
        <p class="mt-1 text-sm text-steel-700">
            Online enrolment: @if ($price && $online)<strong class="text-ok-700">open</strong> @else<strong class="text-danger-700">closed</strong> — {{ ! $price ? 'set a price' : '' }}{{ ! $price && ! $online ? ' and ' : '' }}{{ ! $online ? 'PayMongo keys are not set in the server settings (PAYMONGO_SECRET_KEY and PAYMONGO_WEBHOOK_SECRET)' : '' }}.@endif
        </p>
        <form method="post" action="/admin/course/price" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <label class="grid gap-1 font-semibold text-steel-900">Price (₱)
                <input name="price" inputmode="decimal" value="{{ $price ? rtrim(rtrim(number_format($price, 2, '.', ''), '0'), '.') : '' }}" placeholder="e.g. 4999" class="{{ $field }} font-normal" style="width: 12rem">
            </label>
            <button type="submit" class="{{ $btn }} bg-steel-900 text-white">Save price</button>
        </form>
    </section>
    @endunless

    <section class="{{ $box }}" aria-labelledby="banner-h">
        <h2 id="banner-h" class="text-lg font-bold text-steel-950">Course page banner</h2>
        @php $cb = Course::banner('course'); @endphp
        @if ($cb)<img src="{{ $cb['src'] }}" alt="" class="mt-3 w-full max-w-xl rounded-[var(--radius-control)] border border-steel-200">@else<p class="mt-1 text-sm text-steel-700">No banner yet. Lessons without their own banner also use this one.</p>@endif
        <form method="post" action="/admin/course/banner" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <label class="grid gap-1 font-semibold text-steel-900">Photo (landscape) <input type="file" name="file" accept="image/jpeg,image/png,image/webp" required class="font-normal"></label>
            <button type="submit" class="{{ $btn }} bg-steel-900 text-white">Upload banner</button>
        </form>
        @if ($cb)<form method="post" action="/admin/course/banner" class="mt-2">@csrf<input type="hidden" name="remove" value="1"><button class="text-sm text-danger-700 underline">Remove banner</button></form>@endif
    </section>

    <section class="{{ $box }}" aria-labelledby="lessons-h">
        <h2 id="lessons-h" class="text-lg font-bold text-steel-950">Lessons</h2>
        @if ($lessons->isEmpty())
            <p class="mt-2 text-steel-700">No lessons yet.</p>
        @else
            <ol class="mt-3 divide-y divide-steel-200">
                @foreach ($lessons as $l)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <span><span class="tabular text-steel-600">{{ $loop->iteration }}.</span> <a href="/admin/course/lessons/{{ $l->id }}" class="font-bold text-steel-950 underline">{{ $l->title }}</a></span>
                        <span class="text-sm {{ $l->status === 'published' ? 'text-ok-700' : 'text-steel-600' }}">{{ $l->status === 'published' ? 'Published' : 'Draft' }} · <a href="{{ Course::PATH }}/{{ $l->slug }}" target="_blank" rel="noopener" class="underline">preview</a></span>
                    </li>
                @endforeach
            </ol>
        @endif
        <form method="post" action="/admin/course/lessons" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <label class="grid gap-1 font-semibold text-steel-900">New lesson title
                <input name="title" maxlength="160" required class="{{ $field }} font-normal" style="width: 22rem; max-width: 100%">
            </label>
            <button type="submit" class="{{ $btn }} border border-steel-400 text-steel-900">Add lesson</button>
        </form>
    </section>

    @unless (Course::FREE)
    <section class="{{ $box }}" aria-labelledby="members-h">
        <h2 id="members-h" class="text-lg font-bold text-steel-950">Paid members</h2>
        <p class="mt-1 text-sm text-steel-700">Online payments unlock the course automatically. To give or extend access by hand (for example, paid at the office), enter the member's email. Leave the date empty to add 1 year.</p>
        <form method="post" action="/admin/course/member" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <label class="grid gap-1 font-semibold text-steel-900">Member email
                <input name="email" type="email" required class="{{ $field }} font-normal" style="width: 18rem; max-width: 100%">
            </label>
            <label class="grid gap-1 font-semibold text-steel-900">Paid until (optional)
                <input name="until" type="date" class="{{ $field }} font-normal">
            </label>
            <button type="submit" name="action" value="give" class="{{ $btn }} bg-steel-900 text-white">Give / extend</button>
            <button type="submit" name="action" value="end" class="{{ $btn }} border border-steel-400 text-steel-900">End membership</button>
        </form>
        @if ($members->isNotEmpty())
            <table class="mt-4 w-full text-left text-sm">
                <thead><tr class="border-b border-steel-300"><th class="py-2 pr-3">Member</th><th class="py-2 pr-3">Email</th><th class="py-2">Paid until</th></tr></thead>
                <tbody>
                    @foreach ($members as $m)
                        @php $active = Carbon::parse($m->paid_until)->isFuture(); @endphp
                        <tr class="border-b border-steel-200"><td class="py-2 pr-3">{{ $m->name }}</td><td class="py-2 pr-3 break-all">{{ $m->email }}</td><td class="py-2 {{ $active ? 'text-ok-700' : 'text-steel-500' }}">{{ $d($m->paid_until) }}{{ $active ? '' : ' (ended)' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="{{ $box }}" aria-labelledby="pay-h">
        <h2 id="pay-h" class="text-lg font-bold text-steel-950">Online payments</h2>
        @if ($payments->isEmpty())
            <p class="mt-2 text-steel-700">None yet.</p>
        @else
            <table class="mt-3 w-full text-left text-sm">
                <thead><tr class="border-b border-steel-300"><th class="py-2 pr-3">Reference</th><th class="py-2 pr-3">Member</th><th class="py-2 pr-3">Amount</th><th class="py-2 pr-3">Status</th><th class="py-2">Date</th></tr></thead>
                <tbody>
                    @foreach ($payments as $p)
                        <tr class="border-b border-steel-200"><td class="py-2 pr-3 tabular">{{ $p->reference }}</td><td class="py-2 pr-3">{{ $p->name }}<br><span class="break-all text-steel-600">{{ $p->email }}</span></td><td class="py-2 pr-3 tabular">₱{{ number_format((float) $p->amount_php, 2) }}</td><td class="py-2 pr-3 {{ $p->status === 'paid' ? 'text-ok-700' : 'text-steel-600' }}">{{ $p->status === 'paid' ? 'Paid'.($p->method ? ' ('.$p->method.')' : '') : 'Not paid' }}</td><td class="py-2">{{ $d($p->paid_at ?? $p->created_at) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
    @endunless
</x-admin.layout>
