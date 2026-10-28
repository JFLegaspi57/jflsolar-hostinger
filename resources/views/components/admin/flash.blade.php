{{-- One-line result of the last action (status = success, status_error = problem). --}}
@if (session('status_error'))
    <p role="alert" data-focus-on-load tabindex="-1" {{ $attributes->merge(['class' => 'rounded-[var(--radius-control)] border-2 border-danger-700 bg-danger-50 px-4 py-3 font-semibold text-danger-700 focus:outline-none']) }}>{{ session('status_error') }}</p>
@elseif (session('status'))
    <p role="status" {{ $attributes->merge(['class' => 'rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 px-4 py-3 font-semibold text-ok-700']) }}>{{ session('status') }}</p>
@endif
