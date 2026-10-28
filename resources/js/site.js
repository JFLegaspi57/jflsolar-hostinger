// Public site behaviour. Everything works without JavaScript except the mobile
// menu sheet and the package comparison bar; this file only enhances.

// Native <dialog> open/close (mobile menu).
document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-open-dialog]');
    if (opener) {
        document.getElementById(opener.dataset.openDialog)?.showModal();
        return;
    }
    const closer = e.target.closest('[data-close-dialog]');
    if (closer) {
        closer.closest('dialog')?.close();
        return;
    }
    // Click on the backdrop (the dialog element itself) closes it.
    if (e.target instanceof HTMLDialogElement && e.target.hasAttribute('data-backdrop-close')) {
        e.target.close();
    }
});

// After a form round-trip, move focus to the result or error summary.
document.querySelector('[data-focus-on-load]')?.focus();

// Inquiry form enhancements (the form works without them).
for (const form of document.querySelectorAll('[data-inquiry-form]')) {
    const type = form.querySelector('[data-inquiry-type]');
    const bill = form.querySelector('[data-bill-field]');
    if (type && bill) {
        const relevant = bill.dataset.relevant.split(',');
        type.addEventListener('change', () => {
            bill.hidden = !relevant.includes(type.value);
        });
    }
    form.querySelector('[data-remove-item]')?.addEventListener('click', (e) => {
        e.currentTarget.closest('[data-item-chip]')?.remove();
        form.querySelector('#f-name')?.focus();
    });
    const message = form.querySelector('[data-count]');
    const counter = message && document.getElementById(message.dataset.count);
    message?.addEventListener('input', () => {
        counter.textContent = `${message.value.length} / ${message.maxLength}`;
    });
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-pending-label]');
        if (button) {
            button.disabled = true;
            button.textContent = button.dataset.pendingLabel;
        }
    });
}

// Package comparison: count ticked boxes, cap at data-max, show the sticky bar
// after the first selection. Without JS the form still submits normally.
for (const bar of document.querySelectorAll('[data-compare-bar]')) {
    const formId = bar.dataset.compareBar;
    const max = Number(bar.dataset.max || 3);
    const boxes = () => [...document.querySelectorAll(`input[data-compare][form="${formId}"]`)];
    const status = bar.querySelector('[data-compare-status]');
    const live = bar.querySelector('[data-compare-live]');
    const sticky = bar.querySelector('[data-compare-sticky]');
    const submits = bar.querySelectorAll('[data-compare-submit]');
    const sync = () => {
        const count = boxes().filter((b) => b.checked).length;
        for (const b of boxes()) b.disabled = !b.checked && count >= max;
        status.textContent = count === 0 ? 'Tick “Compare” on 2 or 3 packages to see them side by side.' : `${count} of ${max} selected`;
        live.textContent = `${count} of ${max} selected${count < 2 ? ' — choose one more' : ''}`;
        sticky.classList.toggle('hidden', count === 0);
        for (const s of submits) s.disabled = count < 2;
    };
    document.addEventListener('change', (e) => {
        if (e.target.matches?.('input[data-compare]')) sync();
    });
    sync();
}
