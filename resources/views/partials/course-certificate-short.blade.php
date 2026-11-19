{{-- Account page: one short line about the member's certificate; the full box (previews, GCash QR) is on Your account → Online Course. --}}
@php
    use App\Support\CourseCertificates as Certs;
    $cert = Certs::for($memberId);
    $ready = $cert && (Certs::path($cert, 'image') || Certs::path($cert, 'pdf'));
@endphp
@if ($cert)
    <div class="mt-4 rounded-[var(--radius-control)] border border-gold-500 bg-gold-50 p-4">
        <p class="font-semibold text-steel-950">🎓 Certificate of Completion{{ $ready ? ' – Ready for download' : '' }}</p>
        @if ($cert->paid_at && $ready)
            <p class="mt-1 text-steel-800">Your payment is confirmed.</p>
            <p class="mt-2"><a href="/account/certificate/download" class="{{ \App\Support\Ui::button('primary', 'sm') }}">Download your certificate</a></p>
        @elseif ($ready)
            <p class="mt-1 text-steel-800">Pay via GCash, and you can download it once your payment is confirmed.</p>
            <p class="mt-2"><a href="/account/course#cert-pay-{{ $cert->id }}" class="font-semibold underline">Pay via GCash and download →</a></p>
        @else
            <p class="mt-1 text-steel-800">Your certificate is being prepared.</p>
        @endif
    </div>
    @if ($cert->wrong_popup)
        @include('partials.course-certificate-warning', ['cert' => $cert])
    @endif
@endif
