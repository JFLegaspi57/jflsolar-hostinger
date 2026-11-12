{{-- Shared styles of the net metering guide (English and Tagalog). --}}
<style>
    .nm h2 { margin-bottom: .5rem; font-size: 1.5rem; font-weight: 800; color: var(--color-steel-950); }
    .nm h3 { margin-bottom: .25rem; font-size: 1.125rem; font-weight: 700; color: var(--color-steel-950); }
    .nm p + p { margin-top: .75rem; }
    .nm ul.dots li { margin-left: 1.25rem; list-style: disc; }
    .nm ul.dots li + li { margin-top: .35rem; }
    .nm-steps { counter-reset: step; }
    .nm-steps li { counter-increment: step; position: relative; padding-left: 3.25rem; min-height: 2.5rem; }
    .nm-steps li + li { margin-top: 1.1rem; }
    .nm-steps li::before { content: counter(step); position: absolute; left: 0; top: 0; width: 2.25rem; height: 2.25rem; border-radius: 999px; background: var(--color-gold-500); color: var(--color-steel-950); font-weight: 800; display: grid; place-items: center; }
    .nm a.inline { font-weight: 600; color: var(--color-steel-900); text-decoration: underline; }
    .nm a.inline:hover { color: var(--color-gold-800); }
</style>
