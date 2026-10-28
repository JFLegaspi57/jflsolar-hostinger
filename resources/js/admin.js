// Admin behaviour. Forms submit normally (server-side validation, redirect back
// with the typed values on error); this file adds dialogs, unsaved-change
// protection, in-browser photo downsizing and small conveniences.

// ---------------------------------------------------------------------------
// Native <dialog> confirmations: open, close, backdrop click.
// ---------------------------------------------------------------------------
document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-open-dialog]');
    if (opener) {
        document.getElementById(opener.dataset.openDialog)?.showModal();
        return;
    }
    const closer = e.target.closest('[data-close-dialog]');
    if (closer) closer.closest('dialog')?.close();
    else if (e.target instanceof HTMLDialogElement) e.target.close();
});

// After a round-trip, move focus to the error summary or result message.
document.querySelector('[data-focus-on-load]')?.focus();

// ---------------------------------------------------------------------------
// Unsaved-changes protection. While a form is dirty: page unload, in-app links
// and other forms ask before discarding edits (once per action, even with
// several dirty sections). Submitting the dirty form itself is never blocked.
// ---------------------------------------------------------------------------
const MESSAGE = 'You have unsaved changes on this page. Leave without saving them?';
const dirtyForms = new Set();
const confirmed = new WeakSet();

const ask = (e) => {
    if (confirmed.has(e)) return;
    if (window.confirm(MESSAGE)) confirmed.add(e);
    else {
        e.preventDefault();
        e.stopPropagation();
    }
};

window.addEventListener('beforeunload', (e) => {
    if (dirtyForms.size) e.preventDefault();
});
document.addEventListener(
    'click',
    (e) => {
        if (!dirtyForms.size) return;
        const a = e.target.closest?.('a[href]');
        if (!a || a.target === '_blank' || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin || (url.pathname === location.pathname && url.hash)) return;
        ask(e);
    },
    true,
);
document.addEventListener(
    'submit',
    (e) => {
        const others = [...dirtyForms].filter((f) => f !== e.target);
        if (others.length && !e.defaultPrevented) ask(e);
        if (!e.defaultPrevented) dirtyForms.delete(e.target);
    },
    true,
);

function trackDirty(form, onChange) {
    const mark = (dirty) => {
        if (dirty) dirtyForms.add(form);
        else dirtyForms.delete(form);
        onChange?.(dirty);
    };
    form.addEventListener('input', () => mark(true));
    form.addEventListener('change', () => mark(true));
    if (form.hasAttribute('data-dirty-on-load')) mark(true);
}

for (const form of document.querySelectorAll('[data-admin-form]')) {
    const state = form.querySelector('[data-save-state]');
    trackDirty(form, (dirty) => {
        if (state) state.textContent = dirty ? 'Unsaved changes' : state.dataset.cleanText;
    });
    form.addEventListener('submit', () => {
        if (state) state.textContent = 'Saving…';
        // Lock the buttons so a double click cannot submit twice.
        setTimeout(() => form.querySelectorAll('button[type=submit]').forEach((b) => (b.disabled = true)), 0);
    });
}
for (const form of document.querySelectorAll('[data-image-text]')) {
    const note = form.querySelector('[data-unsaved-note]');
    trackDirty(form, (dirty) => {
        if (note) note.hidden = !dirty;
    });
}

// "Uploading…" / "Sending…" labels on long-running submits.
document.addEventListener('submit', (e) => {
    const button = e.target.querySelector?.('[data-pending-label]');
    if (button && !e.defaultPrevented) {
        setTimeout(() => {
            button.disabled = true;
            button.textContent = button.dataset.pendingLabel;
        }, 0);
    }
});

// ---------------------------------------------------------------------------
// Downsize large photos in the browser so uploads stay fast and under the
// server limit. The server still validates and re-encodes everything.
// ---------------------------------------------------------------------------
const MAX_DIM = 2560;
const TARGET_BYTES = 3.5 * 1024 * 1024;

async function prepare(file) {
    if (!file.type.startsWith('image/') || typeof createImageBitmap !== 'function') return file;
    try {
        const bmp = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, MAX_DIM / Math.max(bmp.width, bmp.height));
        if (scale === 1 && file.size <= TARGET_BYTES) return file;
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bmp.width * scale);
        canvas.height = Math.round(bmp.height * scale);
        canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise((r) => canvas.toBlob(r, 'image/jpeg', 0.88));
        return blob ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file;
    } catch {
        return file; // the server will report an unreadable file
    }
}

for (const input of document.querySelectorAll('input[data-prepare-image]')) {
    const status = document.createElement('p');
    status.className = 'mt-1 text-sm text-steel-600';
    status.setAttribute('role', 'status');
    input.after(status);
    input.addEventListener('change', async () => {
        const file = input.files?.[0];
        const preview = input.dataset.preview && document.getElementById(input.dataset.preview);
        if (!file) {
            if (preview) preview.hidden = true;
            return;
        }
        status.textContent = 'Preparing image…';
        const ready = await prepare(file);
        if (ready !== file) {
            const dt = new DataTransfer();
            dt.items.add(ready);
            input.files = dt.files;
        }
        status.textContent = '';
        if (preview) {
            preview.src = URL.createObjectURL(ready);
            preview.hidden = false;
        }
    });
}
