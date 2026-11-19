{{-- The member's certificate of completion number (CTN), if one has been issued. --}}
@php $cert = \App\Support\CourseCertificates::for($memberId); @endphp
@if ($cert)
    <div class="mt-4 rounded-[var(--radius-control)] border border-gold-500 bg-gold-50 p-4">
        <p class="font-semibold text-steel-950">🎓 Certificate of completion</p>
        <p class="mt-1 text-steel-800">Certificate number (CTN): <strong class="tabular">{{ $cert->ctn }}</strong></p>
        <p class="text-sm text-steel-700">Issued {{ \Illuminate\Support\Carbon::parse($cert->issued_at)->timezone('Asia/Manila')->format('j F Y') }}@if ($cert->completed_on) · course completed {{ \Illuminate\Support\Carbon::parse($cert->completed_on)->format('j F Y') }}@endif</p>
    </div>
@endif
