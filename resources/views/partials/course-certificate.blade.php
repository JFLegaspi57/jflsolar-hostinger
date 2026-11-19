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

        @if (Certs::path($cert, 'image'))
            @php $thumb = ($staff ? '/admin/members/'.$memberId.'/certificate/thumb.jpg' : '/account/certificate/thumb.jpg').'?v='.md5($cert->image.($cert->paid_at ?? '')); @endphp
            @php $thumbImg = '<img src="'.e($thumb).'" alt="Preview of the certificate" width="240" class="mt-3 rounded border border-steel-300 bg-white" style="width:15rem;height:auto">'; @endphp
            @if (! $staff && $cert->paid_at)
                <a href="/account/certificate/download" title="Download your certificate">{!! $thumbImg !!}</a>
                <p class="mt-1 text-sm"><a href="/account/certificate/download" class="font-semibold underline">Download your certificate</a></p>
            @elseif (! $staff)
                @php $qr = Certs::qrUrl(); @endphp
                <div class="mt-3 flex flex-wrap items-start gap-4">
                    <div><a href="#cert-pay-{{ $cert->id }}" title="How to get your certificate">{!! str_replace('class="mt-3 ', 'class="', $thumbImg) !!}</a>
                        <p class="mt-1 text-sm text-steel-700">Your certificate (preview)</p></div>
                    @if ($qr)
                        <div><img src="{{ $qr }}" alt="GCash QR code of JFL Solar for the certificate payment" width="160" class="rounded border border-steel-300 bg-white" style="width:10rem;height:auto">
                            <p class="mt-1 text-sm text-steel-700">Pay {{ $fee }} with GCash</p></div>
                    @endif
                </div>
            @else
                {!! $thumbImg !!}
            @endif
        @endif

        @if ($cert->paid_at)
            <p class="mt-3 text-sm font-semibold" style="color:#166534">✅ Paid via GCash{{ $cert->gcash_ref ? ' (ref. '.$cert->gcash_ref.')' : '' }} · {{ \Illuminate\Support\Carbon::parse($cert->paid_at)->timezone('Asia/Manila')->format('j M Y') }}</p>
            @if (! $staff && ! Certs::path($cert, 'image') && Certs::path($cert, 'pdf'))
                <p class="mt-2"><a href="/account/certificate/download" class="{{ \App\Support\Ui::button('primary', 'sm') }}">Download your certificate</a></p>
            @endif
        @elseif (! $staff)
            @if (Certs::path($cert, 'image') || Certs::path($cert, 'pdf'))
                @php $qrSide = ! Certs::path($cert, 'image') ? Certs::qrUrl() : null; @endphp {{-- with a preview, the QR sits beside the preview instead --}}
                <div class="mt-3 flex flex-wrap items-start gap-4">
                    <div class="min-w-0 flex-1" style="min-width:15rem">
                        <p id="cert-pay-{{ $cert->id }}" class="text-steel-800"><strong>To download your certificate, pay {{ $fee }} via GCash</strong> by scanning the QR code with your GCash app. After paying, enter the GCash reference number below. We'll check the payment and unlock your download.</p>
                        <p class="mt-2 font-bold" style="color:#991b1b">Important! Type the GCash reference number exactly as it appears on your receipt. We can only confirm your payment and unlock your certificate with the correct number.</p>
                        @if (! Certs::qrUrl())
                            <p class="mt-2 text-sm text-steel-700">The GCash QR code will be shown here soon.</p>
                        @endif
                        @if ($cert->gcash_ref)
                            <p class="mt-3 text-sm text-steel-800">⏳ We received your reference number <strong>{{ $cert->gcash_ref }}</strong> on {{ \Illuminate\Support\Carbon::parse($cert->ref_at)->timezone('Asia/Manila')->format('j M Y, g:i A') }}. We'll unlock your download as soon as the payment is confirmed.</p>
                        @endif
                        <form method="post" action="/account/certificate/reference" class="mt-3 flex flex-wrap items-end gap-2">
                            @csrf
                            <label class="text-sm"><span class="font-semibold">GCash reference number</span><br><input name="reference" required maxlength="60" inputmode="numeric" class="mt-1 min-h-11 rounded-[var(--radius-control)] border border-steel-400 px-3" placeholder="e.g. 1234 567 890123"></label>
                            <button type="submit" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">{{ $cert->gcash_ref ? 'Send again' : 'Send' }}</button>
                        </form>
                    </div>
                    @if ($qrSide)
                        <div><img src="{{ $qrSide }}" alt="GCash QR code of JFL Solar for the certificate payment" width="190" class="rounded border border-steel-300 bg-white" style="width:11.875rem;height:auto">
                            <p class="mt-1 text-center text-sm text-steel-700">Scan with your GCash app</p></div>
                    @endif
                </div>
            @else
                <p class="mt-3 text-sm text-steel-700">Your certificate is being prepared.</p>
            @endif
        @endif

        @if ($staff)
            <div class="mt-4 space-y-3 border-t border-gold-300 pt-3 text-sm">
                <p><strong>Payment ({{ $fee }} via GCash):</strong>
                    @if ($cert->paid_at) paid. @elseif ($cert->gcash_ref) the member sent reference <strong>{{ $cert->gcash_ref }}</strong> on {{ \Illuminate\Support\Carbon::parse($cert->ref_at)->timezone('Asia/Manila')->format('j M Y, g:i A') }}. Check it in GCash, then mark it paid. @else not paid yet. @endif</p>
                <form method="post" action="/admin/members/{{ $memberId }}/certificate">@csrf
                    <input type="hidden" name="action" value="{{ $cert->paid_at ? 'unpaid' : 'paid' }}">
                    <button type="submit" class="{{ \App\Support\Ui::button($cert->paid_at ? 'secondary' : 'primary', 'sm') }}">{{ $cert->paid_at ? 'Mark as not paid (locks the download)' : 'Mark as paid (unlocks the download)' }}</button>
                </form>
                <form method="post" action="/admin/members/{{ $memberId }}/certificate" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">@csrf
                    <input type="hidden" name="action" value="upload">
                    <label><span class="font-semibold">Certificate picture (PNG/JPG){{ Certs::path($cert, 'image') ? ' ✓' : '' }}</span><br><input type="file" name="image" accept="image/png,image/jpeg" class="mt-1"></label>
                    <label><span class="font-semibold">Certificate PDF (optional){{ Certs::path($cert, 'pdf') ? ' ✓' : '' }}</span><br><input type="file" name="pdf" accept="application/pdf" class="mt-1"></label>
                    <button type="submit" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Upload</button>
                </form>
                <p class="text-steel-600">The member sees a small preview marked "PREVIEW" until it's paid. After you mark it paid, they can download the PDF (or the picture, if there's no PDF).</p>
            </div>
        @endif
    </div>
@endif
