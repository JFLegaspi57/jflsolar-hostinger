@if (session('quote_status'))
    <p role="status" class="mb-6 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-4 font-semibold text-ok-700">{{ session('quote_status') }}</p>
@endif
@if (session('quote_error'))
    <p role="alert" class="mb-6 rounded-[var(--radius-control)] border border-[#f3c3be] bg-danger-50 p-4 font-semibold text-danger-700">{{ session('quote_error') }}</p>
@endif
@if (session('quote_sent'))
    <div role="status" class="mb-6 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-4 text-ok-700">
        <p class="font-bold">Thank you! Your quotation request was sent to JFL Solar.</p>
        <p class="mt-1">Our team will reply with a formal quotation by email or phone. Your list stays here, so you can keep working on it and send it again if anything changes.</p>
    </div>
@endif
