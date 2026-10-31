@if (session('account_status'))
    <p role="status" class="rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-4 font-semibold text-ok-700">{{ session('account_status') }}</p>
@endif
@if (session('account_error') || session('status_error'))
    <p role="alert" class="rounded-[var(--radius-control)] border border-[#f3c3be] bg-danger-50 p-4 font-semibold text-danger-700">{{ session('account_error') ?? session('status_error') }}</p>
@endif
