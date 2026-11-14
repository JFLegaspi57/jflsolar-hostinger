{{-- "Confirm Delete" pop-up for Make a Quotation. Each Delete button is a <details class="mq-del"> with its own form (works without JavaScript);
     with JavaScript, clicking Delete opens this window instead, filled in for that quotation. --}}
<dialog id="mq-confirm" class="mq-dialog" aria-labelledby="mq-confirm-h" aria-describedby="mq-confirm-text">
    <form method="post" action="" id="mq-confirm-form">
        @csrf
        <h2 id="mq-confirm-h" class="text-xl font-bold text-steel-950">Confirm Delete</h2>
        <p id="mq-confirm-text" class="mt-2 text-steel-800"></p>
        <div class="mq-dialog-actions">
            <button type="button" class="{{ \App\Support\Ui::button('secondary') }}" data-cancel autofocus>Cancel</button>
            <button type="submit" class="{{ \App\Support\Ui::button('danger') }}">Delete</button>
        </div>
    </form>
</dialog>
<style>
    .mq-dialog { margin: auto; max-height: calc(100vh - 2rem); width: min(28rem, calc(100vw - 2rem)); border: 0; border-top: 4px solid var(--color-danger-700); border-radius: var(--radius-panel); padding: 1.5rem; box-shadow: 0 24px 60px -12px rgb(15 20 28 / .45); }
    .mq-dialog::backdrop { background: rgb(15 20 28 / .55); }
    .mq-dialog-actions { margin-top: 1.5rem; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .5rem; }
</style>
<script>
    (() => {
        const dialog = document.getElementById('mq-confirm');
        if (!dialog || typeof dialog.showModal !== 'function') return; // old browsers keep the inline confirmation
        const form = document.getElementById('mq-confirm-form');
        const text = document.getElementById('mq-confirm-text');
        document.querySelectorAll('details.mq-del').forEach(d => {
            const inline = d.querySelector('form');
            d.querySelector('summary').addEventListener('click', e => {
                e.preventDefault();
                document.querySelectorAll('details.mq-rename[open]').forEach(o => o.open = false);
                form.action = inline.getAttribute('action');
                text.textContent = inline.querySelector('p').textContent.trim();
                dialog.showModal();
            });
        });
        dialog.querySelector('[data-cancel]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', e => { if (e.target === dialog) dialog.close(); }); // click outside the window
    })();
</script>
