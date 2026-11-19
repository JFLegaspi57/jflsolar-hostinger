{{-- The member's certificate of completion: number (CTN), preview, and download once paid via GCash.
     $memberId; $staff = true on the admin member page. --}}
@php
    use App\Support\CourseCertificates as Certs;
    $cert = Certs::for($memberId);
    $staff = $staff ?? false;
    $fee = '₱'.number_format(Certs::FEE_PHP);
@endphp
@if ($cert)
    <div class="mt-4 rounded-[var(--radius-control)] border border-gold-500 bg-gold-50 p-4">
        <p class="font-semibold text-steel-950">🎓 Certificate of completion</p>
        <p class="mt-1 text-steel-800">Certificate number (CTN): <strong class="tabular">{{ $cert->ctn }}</strong></p>
        <p class="text-sm text-steel-700">Issued {{ \Illuminate\Support\Carbon::parse($cert->issued_at)->timezone('Asia/Manila')->format('j F Y') }}@if ($cert->completed_on) · course completed {{ \Illuminate\Support\Carbon::parse($cert->completed_on)->format('j F Y') }}@endif</p>

        @php
            $pages = array_values(array_filter([Certs::path($cert, 'image') ? 1 : null, Certs::path($cert, 'image2') ? 2 : null]));
            $thumbUrl = fn (int $p) => ($staff ? '/admin/members/'.$memberId.'/certificate/thumb.jpg' : '/account/certificate/thumb.jpg').'?page='.$p.'&v='.md5(($p === 2 ? $cert->image2 : $cert->image).($cert->paid_at ?? ''));
            $pageLabel = fn (int $p) => $p === 1 ? 'Certificate' : 'Lessons page';
            $hasFiles = $pages || Certs::path($cert, 'pdf');
            $qr = Certs::qrUrl();
        @endphp

        @if ($staff)
            @if ($pages)
                <div class="mt-3 flex flex-wrap items-start gap-3">
                    @foreach ($pages as $p)
                        <div><img src="{{ $thumbUrl($p) }}" alt="{{ $pageLabel($p) }} preview" width="200" class="rounded border border-steel-300 bg-white" style="width:12.5rem;height:auto"><p class="mt-1 text-sm text-steel-700">{{ $pageLabel($p) }}</p></div>
                    @endforeach
                </div>
            @endif
            @if ($cert->paid_at)
                <p class="mt-3 text-sm font-semibold" style="color:#166534">✅ Paid via GCash{{ $cert->gcash_ref ? ' (ref. '.$cert->gcash_ref.')' : '' }} · {{ \Illuminate\Support\Carbon::parse($cert->paid_at)->timezone('Asia/Manila')->format('j M Y') }}</p>
            @endif
        @elseif ($cert->paid_at)
            <p class="mt-3 text-sm font-semibold" style="color:#166534">✅ Paid via GCash{{ $cert->gcash_ref ? ' (ref. '.$cert->gcash_ref.')' : '' }} · {{ \Illuminate\Support\Carbon::parse($cert->paid_at)->timezone('Asia/Manila')->format('j M Y') }}</p>
            @if ($pages)
                <div class="mt-3 flex flex-wrap items-start gap-3">
                    @foreach ($pages as $p)
                        <div><a href="/account/certificate/download" title="Download your certificate"><img src="{{ $thumbUrl($p) }}" alt="{{ $pageLabel($p) }} preview" width="220" class="rounded border border-steel-300 bg-white" style="width:13.75rem;height:auto"></a><p class="mt-1 text-sm text-steel-700">{{ $pageLabel($p) }}</p></div>
                    @endforeach
                </div>
            @endif
            @if ($hasFiles)
                <p class="mt-3"><a href="/account/certificate/download" class="{{ \App\Support\Ui::button('primary', 'sm') }}">Download your certificate</a></p>
            @endif
        @elseif ($hasFiles)
            <p id="cert-pay-{{ $cert->id }}" class="mt-3 text-steel-800"><strong>To download your certificate, pay {{ $fee }} via GCash</strong> by scanning the QR code with your GCash app. After paying, enter the GCash reference number below. We'll check the payment and unlock your download.</p>
            <p class="mt-2 font-bold" style="color:#991b1b">Important! Type the GCash reference number exactly as it appears on your receipt. We can only confirm your payment and unlock your certificate with the correct number.</p>
            @if ((int) $cert->wrong_refs > 0 && ! Certs::blocked($cert))
                <p class="mt-2 text-sm font-semibold" style="color:#991b1b">⚠️ {{ $cert->wrong_refs }} of {{ Certs::MAX_WRONG_REFS }} reference numbers you sent did not match any payment.</p>
            @endif
            {{-- GCash QR on the left, then the certificate pages on its right --}}
            <div class="mt-3 flex flex-wrap items-start gap-3">
                @if ($qr)
                    <div><img src="{{ $qr }}" alt="GCash QR code of JFL Solar for the certificate payment" width="170" class="rounded border border-steel-300 bg-white" style="width:10.625rem;height:auto"><p class="mt-1 text-sm text-steel-700">Pay {{ $fee }} with GCash</p></div>
                @else
                    <p class="text-sm text-steel-700">The GCash QR code will be shown here soon.</p>
                @endif
                @foreach ($pages as $p)
                    <div><img src="{{ $thumbUrl($p) }}" alt="{{ $pageLabel($p) }} preview" width="220" class="rounded border border-steel-300 bg-white" style="width:13.75rem;height:auto"><p class="mt-1 text-sm text-steel-700">{{ $pageLabel($p) }} (preview)</p></div>
                @endforeach
            </div>
            @if ($cert->gcash_ref)
                <p class="mt-3 text-sm text-steel-800">⏳ We received your reference number <strong>{{ $cert->gcash_ref }}</strong> on {{ \Illuminate\Support\Carbon::parse($cert->ref_at)->timezone('Asia/Manila')->format('j M Y, g:i A') }}. We'll unlock your download as soon as the payment is confirmed.</p>
            @endif
            @if (Certs::blocked($cert))
                <p class="mt-3 rounded border p-3 text-sm font-semibold" style="border-color:#fca5a5;background:#fef2f2;color:#991b1b">{{ Certs::MAX_WRONG_REFS }} reference numbers you sent did not match any payment, so you can no longer send one here. If you paid, please call our Sales team at 0969 471 2098 with your GCash receipt.</p>
            @else
                <form method="post" action="/account/certificate/reference" class="mt-3 flex flex-wrap items-end gap-2">
                    @csrf
                    <label class="text-sm"><span class="font-semibold">GCash reference number</span><br><input name="reference" required maxlength="60" inputmode="numeric" class="mt-1 min-h-11 rounded-[var(--radius-control)] border border-steel-400 px-3" placeholder="e.g. 1234 567 890123"></label>
                    <button type="submit" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">{{ $cert->gcash_ref ? 'Send again' : 'Send' }}</button>
                </form>
            @endif
        @else
            <p class="mt-3 text-sm text-steel-700">Your certificate is being prepared.</p>
        @endif

        @if (! $staff && $cert->wrong_popup)
            @include('partials.course-certificate-warning', ['cert' => $cert])
        @endif

        @if ($staff)
            <div class="mt-4 space-y-3 border-t border-gold-300 pt-3 text-sm">
                <p><strong>Payment ({{ $fee }} via GCash):</strong>
                    @if ($cert->paid_at) paid. @elseif ($cert->gcash_ref) the member sent reference <strong>{{ $cert->gcash_ref }}</strong> on {{ \Illuminate\Support\Carbon::parse($cert->ref_at)->timezone('Asia/Manila')->format('j M Y, g:i A') }}. Check it in GCash, then mark it paid. @else not paid yet. @endif</p>
                @if (! $cert->paid_at && $cert->gcash_ref)
                    <form method="post" action="/admin/members/{{ $memberId }}/certificate">@csrf
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="{{ \App\Support\Ui::button('secondary', 'sm') }}" style="color:#991b1b;border-color:#dc2626">Reject: no GCash payment with this reference</button>
                        <span class="text-steel-600">The member gets a pop-up warning ({{ (int) $cert->wrong_refs + 1 }} of {{ Certs::MAX_WRONG_REFS }}).</span>
                    </form>
                @endif
                @if ((int) $cert->wrong_refs > 0)
                    <p style="color:#991b1b"><strong>Wrong reference numbers:</strong> {{ $cert->wrong_refs }} of {{ Certs::MAX_WRONG_REFS }} (last: {{ $cert->wrong_last }}){{ Certs::blocked($cert) ? ' — blocked from sending more.' : '' }}</p>
                    <form method="post" action="/admin/members/{{ $memberId }}/certificate">@csrf<input type="hidden" name="action" value="reset-wrong"><button type="submit" class="text-sm underline text-steel-700">Reset the count (give the 3 tries back)</button></form>
                @endif
                <form method="post" action="/admin/members/{{ $memberId }}/certificate">@csrf
                    <input type="hidden" name="action" value="{{ $cert->paid_at ? 'unpaid' : 'paid' }}">
                    <button type="submit" class="{{ \App\Support\Ui::button($cert->paid_at ? 'secondary' : 'primary', 'sm') }}">{{ $cert->paid_at ? 'Mark as not paid (locks the download)' : 'Mark as paid (unlocks the download)' }}</button>
                </form>
                <form method="post" action="/admin/members/{{ $memberId }}/certificate" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">@csrf
                    <input type="hidden" name="action" value="upload">
                    <label><span class="font-semibold">Certificate picture, page 1 (PNG/JPG){{ Certs::path($cert, 'image') ? ' ✓' : '' }}</span><br><input type="file" name="image" accept="image/png,image/jpeg" class="mt-1"></label>
                    <label><span class="font-semibold">Lessons page, page 2 (PNG/JPG){{ Certs::path($cert, 'image2') ? ' ✓' : '' }}</span><br><input type="file" name="image2" accept="image/png,image/jpeg" class="mt-1"></label>
                    <label><span class="font-semibold">Certificate PDF (optional){{ Certs::path($cert, 'pdf') ? ' ✓' : '' }}</span><br><input type="file" name="pdf" accept="application/pdf" class="mt-1"></label>
                    <button type="submit" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Upload</button>
                </form>
                <p class="text-steel-600">The member sees a small preview marked "PREVIEW" until it's paid. After you mark it paid, they can download the PDF (or the picture, if there's no PDF).</p>
            </div>
        @endif
    </div>
@endif
