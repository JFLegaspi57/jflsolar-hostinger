{{-- Website chat assistant (AI). Shown only when Chatbot::available(). Progressive enhancement: without JavaScript nothing shows. --}}
@php $chatPhone = \App\Support\PublicContent::settings()->phone_display; @endphp
<div id="jfl-chat" data-csrf="{{ csrf_token() }}" hidden>
    <style>
        #jfl-chat-btn { position: fixed; right: 1rem; bottom: 1rem; z-index: 45; display: inline-flex; align-items: center; gap: .5rem; min-height: 3.25rem; padding: 0 1.1rem 0 .95rem; border-radius: 999px; border: 0; background: var(--color-steel-900); color: #fff; font-weight: 700; font-size: .95rem; box-shadow: 0 8px 24px rgb(0 0 0 / .22); cursor: pointer; }
        #jfl-chat-btn:hover { background: var(--color-steel-800); }
        #jfl-chat-btn:focus-visible, #jfl-chat button:focus-visible, #jfl-chat textarea:focus-visible, #jfl-chat a:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
        #jfl-chat-btn svg { width: 1.35rem; height: 1.35rem; }
        body:has([data-compare-sticky]:not(.hidden)) #jfl-chat-btn { bottom: 4.75rem; }
        #jfl-chat-panel { position: fixed; right: 1rem; bottom: 1rem; z-index: 46; width: 24rem; max-width: calc(100vw - 2rem); height: min(40rem, calc(100vh - 6.5rem)); display: flex; flex-direction: column; overflow: hidden; border-radius: 1rem; border: 1px solid var(--color-steel-200); background: #fff; box-shadow: 0 16px 48px rgb(0 0 0 / .25); }
        #jfl-chat-panel[hidden] { display: none; }
        .jc-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .75rem .9rem; background: var(--color-steel-900); color: #fff; }
        .jc-head p { margin: 0; font-weight: 700; line-height: 1.2; }
        .jc-head small { display: block; font-weight: 400; font-size: .78rem; color: var(--color-steel-200); }
        .jc-icon { display: inline-flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; border: 0; border-radius: .5rem; background: transparent; color: inherit; cursor: pointer; }
        .jc-icon:hover { background: rgb(255 255 255 / .12); }
        .jc-log { flex: 1; overflow-y: auto; overscroll-behavior: contain; padding: .9rem; display: flex; flex-direction: column; gap: .6rem; background: var(--color-steel-50); }
        .jc-msg { max-width: 88%; padding: .6rem .8rem; border-radius: .9rem; line-height: 1.45; font-size: .95rem; white-space: pre-wrap; word-wrap: break-word; }
        .jc-bot { align-self: flex-start; background: #fff; border: 1px solid var(--color-steel-200); color: var(--color-steel-900); border-bottom-left-radius: .25rem; }
        .jc-me { align-self: flex-end; background: var(--color-steel-900); color: #fff; border-bottom-right-radius: .25rem; }
        .jc-bot a { color: var(--color-steel-950); font-weight: 600; text-decoration: underline; }
        .jc-note { align-self: center; font-size: .8rem; color: var(--color-steel-600); text-align: center; }
        .jc-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
        .jc-chip { min-height: 2.5rem; padding: 0 .8rem; border-radius: 999px; border: 1px solid var(--color-steel-300); background: #fff; color: var(--color-steel-900); font-size: .85rem; font-weight: 600; cursor: pointer; text-align: left; }
        .jc-chip:hover { border-color: var(--color-steel-900); }
        .jc-dots { display: inline-flex; gap: .25rem; } .jc-dots i { width: .45rem; height: .45rem; border-radius: 50%; background: var(--color-steel-400); animation: jcb 1s infinite ease-in-out; } .jc-dots i:nth-child(2) { animation-delay: .15s; } .jc-dots i:nth-child(3) { animation-delay: .3s; }
        @keyframes jcb { 0%, 80%, 100% { opacity: .3; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-3px); } }
        @media (prefers-reduced-motion: reduce) { .jc-dots i { animation: none; } }
        .jc-form { display: flex; gap: .5rem; align-items: flex-end; padding: .6rem; border-top: 1px solid var(--color-steel-200); background: #fff; }
        .jc-form textarea { flex: 1; min-height: 2.75rem; max-height: 7rem; resize: none; padding: .6rem .7rem; border: 1px solid var(--color-steel-500); border-radius: .6rem; font: inherit; font-size: 1rem; color: var(--color-steel-950); }
        .jc-send { min-height: 2.75rem; padding: 0 1rem; border: 0; border-radius: .6rem; background: var(--color-gold-500); color: var(--color-steel-950); font-weight: 700; cursor: pointer; }
        .jc-send:disabled { opacity: .5; cursor: not-allowed; }
        .jc-foot { padding: 0 .75rem .55rem; background: #fff; font-size: .72rem; color: var(--color-steel-600); }
        .jc-foot a { color: inherit; text-decoration: underline; }
        @media (max-width: 479.98px) {
            #jfl-chat-btn .jc-label { display: none; } #jfl-chat-btn { padding: 0; width: 3.25rem; justify-content: center; }
            #jfl-chat-panel { right: 0; bottom: 0; width: 100vw; max-width: 100vw; height: 88vh; height: 88dvh; border-radius: 1rem 1rem 0 0; }
        }
        @media print { #jfl-chat { display: none !important; } }
    </style>
    <button type="button" id="jfl-chat-btn" aria-expanded="false" aria-controls="jfl-chat-panel">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.8 7L4 20l1.1-4.4A8 8 0 1 1 21 12z"/><path d="M8.5 11h.01M12 11h.01M15.5 11h.01"/></svg>
        <span class="jc-label">Chat with us</span>
    </button>
    <section id="jfl-chat-panel" role="dialog" aria-label="JFL Solar chat assistant" hidden>
        <div class="jc-head">
            <p>JFL Solar Assistant<small>Ask about prices, packages and ordering</small></p>
            <div>
                <button type="button" class="jc-icon" data-chat-reset title="Start a new chat"><svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg><span class="sr-only">Start a new chat</span></button>
                <button type="button" class="jc-icon" data-chat-close title="Close chat"><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg><span class="sr-only">Close chat</span></button>
            </div>
        </div>
        <div class="jc-log" data-chat-log role="log" aria-live="polite"></div>
        <form class="jc-form" data-chat-form>
            <label for="jfl-chat-input" class="sr-only">Your message</label>
            <textarea id="jfl-chat-input" rows="1" maxlength="1000" placeholder="Type your question…" autocomplete="off"></textarea>
            <button type="submit" class="jc-send">Send</button>
        </form>
        <p class="jc-foot">Answers are by AI and may be wrong — Sales confirms prices and stock. Don’t share card numbers or passwords. <a href="/privacy#chat">Privacy</a></p>
    </section>
    <script>
        (() => {
            const root = document.getElementById('jfl-chat');
            if (!root || !window.fetch) return;
            root.hidden = false;
            const btn = root.querySelector('#jfl-chat-btn'), panel = root.querySelector('#jfl-chat-panel'), log = root.querySelector('[data-chat-log]');
            const form = root.querySelector('[data-chat-form]'), input = root.querySelector('#jfl-chat-input'), send = root.querySelector('.jc-send');
            const KEY = 'jfl-chat-v1';
            const greeting = 'Hi! I’m the JFL Solar assistant. Ask me about our solar panels, inverters, batteries, packages, prices, pickup and payment. Puwede rin sa Tagalog! 😊';
            const starters = ['How much is the 6kW Trio Package?', 'Which battery should I choose?', 'How do I order and pick up?', 'Talk to a person'];
            let messages = [], busy = false;
            try { const saved = JSON.parse(sessionStorage.getItem(KEY) || '[]'); if (Array.isArray(saved)) messages = saved.filter((m) => m && (m.role === 'user' || m.role === 'assistant') && typeof m.content === 'string').slice(-24); } catch (e) {}
            const save = () => { try { sessionStorage.setItem(KEY, JSON.stringify(messages.slice(-24))); } catch (e) {} };

            // Safe rendering: text nodes only; **bold** and [label](/site-path | tel: | https://jflsolar.ph…) links.
            const linkOk = (u) => /^\/(?!\/)/.test(u) || /^tel:\+?[0-9]+$/.test(u) || /^https:\/\/(www\.)?jflsolar\.ph(\/|$)/.test(u) || /^https:\/\/(wa\.me|m\.me|www\.facebook\.com|viber\.me)\//.test(u);
            const render = (el, text) => {
                const re = /\[([^\]\n]{1,120})\]\(([^)\s]{1,300})\)|\*\*([^*\n]{1,200})\*\*/g;
                let last = 0, m;
                while ((m = re.exec(text))) {
                    el.append(document.createTextNode(text.slice(last, m.index)));
                    if (m[1] !== undefined && linkOk(m[2])) {
                        const a = document.createElement('a'); a.textContent = m[1]; a.href = m[2];
                        if (/^https?:/.test(m[2]) && !/jflsolar\.ph/.test(m[2])) { a.target = '_blank'; a.rel = 'noopener'; }
                        el.append(a);
                    } else if (m[1] !== undefined) { el.append(document.createTextNode(m[1])); }
                    else { const b = document.createElement('strong'); b.textContent = m[3]; el.append(b); }
                    last = re.lastIndex;
                }
                el.append(document.createTextNode(text.slice(last)));
            };
            const bubble = (role, text) => { const d = document.createElement('div'); d.className = 'jc-msg ' + (role === 'user' ? 'jc-me' : 'jc-bot'); render(d, text); log.append(d); log.scrollTop = log.scrollHeight; return d; };
            const note = (text) => { const p = document.createElement('p'); p.className = 'jc-note'; p.textContent = text; log.append(p); log.scrollTop = log.scrollHeight; };
            const draw = () => {
                log.textContent = '';
                bubble('assistant', greeting);
                if (!messages.length) {
                    const chips = document.createElement('div'); chips.className = 'jc-chips';
                    starters.forEach((s) => { const c = document.createElement('button'); c.type = 'button'; c.className = 'jc-chip'; c.textContent = s; c.addEventListener('click', () => ask(s)); chips.append(c); });
                    log.append(chips);
                }
                messages.forEach((m) => bubble(m.role, m.content));
            };
            const open = (yes) => {
                panel.hidden = !yes; btn.hidden = yes; btn.setAttribute('aria-expanded', String(yes));
                if (yes) { draw(); setTimeout(() => input.focus(), 30); } else { btn.focus(); }
            };
            const ask = async (text) => {
                text = text.trim();
                if (!text || busy) return;
                busy = true; send.disabled = true;
                root.querySelector('.jc-chips')?.remove();
                messages.push({ role: 'user', content: text }); save();
                bubble('user', text);
                const typing = document.createElement('div'); typing.className = 'jc-msg jc-bot'; typing.innerHTML = '<span class="jc-dots" aria-label="Typing"><i></i><i></i><i></i></span>'; log.append(typing); log.scrollTop = log.scrollHeight;
                try {
                    const r = await fetch('/chat', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf }, body: JSON.stringify({ messages }) });
                    typing.remove();
                    if (r.status === 419) { messages.pop(); save(); note('This page has been open a while. Please reload the page and ask again.'); return; }
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.reply) { messages.pop(); save(); note(data.error || 'Sorry, something went wrong. Please try again, or call {{ $chatPhone }}.'); return; }
                    messages.push({ role: 'assistant', content: data.reply }); save();
                    bubble('assistant', data.reply);
                } catch (e) {
                    typing.remove(); messages.pop(); save();
                    note('You seem to be offline. Please check your connection and try again.');
                } finally { busy = false; send.disabled = false; input.focus(); }
            };
            btn.addEventListener('click', () => open(true));
            root.querySelector('[data-chat-close]').addEventListener('click', () => open(false));
            root.querySelector('[data-chat-reset]').addEventListener('click', () => { messages = []; save(); draw(); input.focus(); });
            panel.addEventListener('keydown', (e) => { if (e.key === 'Escape') open(false); });
            form.addEventListener('submit', (e) => { e.preventDefault(); const t = input.value; input.value = ''; input.style.height = ''; ask(t); });
            input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
            input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 112) + 'px'; });
        })();
    </script>
</div>
