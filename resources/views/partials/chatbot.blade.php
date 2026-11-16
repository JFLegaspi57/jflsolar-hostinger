{{-- Website chat assistant (AI). Shown only when Chatbot::available(). Progressive enhancement: without JavaScript nothing shows. --}}
@php $chatPhone = \App\Support\PublicContent::settings()->phone_display; @endphp
<div id="jfl-chat" data-csrf="{{ csrf_token() }}" hidden>
    <style>
        #jfl-chat-btn { position: fixed; right: 1rem; bottom: 1rem; z-index: 45; display: inline-flex; align-items: center; gap: .5rem; min-height: 3.25rem; padding: 0 1.1rem 0 .95rem; border-radius: 999px; border: 0; background: var(--color-steel-900); color: #fff; font-weight: 700; font-size: .95rem; box-shadow: 0 8px 24px rgb(0 0 0 / .22); cursor: pointer; }
        #jfl-chat-btn:hover { background: var(--color-steel-800); }
        #jfl-chat-btn:focus-visible, #jfl-chat button:focus-visible, #jfl-chat textarea:focus-visible, #jfl-chat a:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
        #jfl-chat-btn svg { width: 1.35rem; height: 1.35rem; }
        #jfl-chat .jc-face { width: 2.4rem; height: 2.4rem; flex: none; border-radius: 999px; background: #e8f2fb; box-shadow: 0 0 0 2px rgb(255 255 255 / .85); margin-left: -.45rem; }
        #jfl-chat .jc-face-head { margin-left: 0; width: 2rem; height: 2rem; }
        /* Buckny comes alive: waves on hover, breathes while the chat is open, bounces while he is typing. */
        @keyframes jc-wave { 0%, 100% { transform: rotate(0) } 20% { transform: rotate(-14deg) scale(1.08) } 40% { transform: rotate(12deg) scale(1.08) } 60% { transform: rotate(-8deg) scale(1.05) } 80% { transform: rotate(5deg) } }
        @keyframes jc-bob { 0%, 100% { transform: translateY(0) rotate(0) } 25% { transform: translateY(-2px) rotate(-3deg) } 75% { transform: translateY(1px) rotate(3deg) } }
        @keyframes jc-talk { 0%, 100% { transform: translateY(0) scale(1) } 50% { transform: translateY(-4px) scale(1.06) } }
        @keyframes jc-peek { 0%, 86%, 100% { transform: rotate(0) } 90% { transform: rotate(-12deg) } 94% { transform: rotate(10deg) } }
        #jfl-chat .jc-face { transform-origin: 50% 85%; }
        #jfl-chat-btn .jc-face { animation: jc-peek 6s ease-in-out 2s infinite; }
        #jfl-chat-btn:hover .jc-face, #jfl-chat-btn:focus-visible .jc-face { animation: jc-wave .7s ease-in-out; }
        #jfl-chat-panel .jc-face-head { animation: jc-bob 3s ease-in-out infinite; cursor: pointer; }
        #jfl-chat-panel .jc-face-head:hover { animation: jc-wave .7s ease-in-out; }
        #jfl-chat-panel.is-thinking .jc-face-head { animation: jc-talk .55s ease-in-out infinite; }
        /* Buckny says hello: a speech bubble above the button on hover/focus, and above his face in the chat when it opens or he is clicked. */
        .jc-say { position: fixed; right: 1rem; bottom: 4.6rem; z-index: 45; max-width: min(16rem, calc(100vw - 2rem)); padding: .6rem .8rem; border-radius: .9rem .9rem .2rem .9rem; background: #fff; color: var(--color-steel-950); font-size: .9rem; font-weight: 600; line-height: 1.35; box-shadow: 0 8px 24px rgb(15 23 42 / .22), 0 0 0 1px rgb(15 23 42 / .08); opacity: 0; transform: translateY(6px) scale(.96); transform-origin: 90% 100%; transition: opacity .18s ease, transform .18s ease; pointer-events: none; }
        #jfl-chat-btn:hover + .jc-say, #jfl-chat-btn:focus-visible + .jc-say, .jc-say.is-on { opacity: 1; transform: none; }
        #jfl-chat-btn[hidden] + .jc-say { display: none; }
        body:has([data-compare-sticky]:not(.hidden)) #jfl-chat-btn + .jc-say { bottom: 8.35rem; }
        #jfl-chat-btn:hover .jc-face, #jfl-chat-btn:focus-visible .jc-face { animation: jc-wave .7s ease-in-out, jc-talk .32s ease-in-out .7s 5; }
        .jc-head { position: relative; }
        .jc-say-head { position: absolute; left: .6rem; top: calc(100% + .4rem); right: auto; bottom: auto; z-index: 3; border-radius: .2rem .9rem .9rem .9rem; transform-origin: 10% 0; }
        #jfl-chat-panel.is-saying .jc-face-head { animation: jc-talk .32s ease-in-out 6; }
        @media (prefers-reduced-motion: reduce) { #jfl-chat .jc-face, #jfl-chat-panel .jc-face-head, #jfl-chat-panel.is-thinking .jc-face-head, #jfl-chat-panel.is-saying .jc-face-head { animation: none !important; } .jc-say { transition: none; } }
        body:has([data-compare-sticky]:not(.hidden)) #jfl-chat-btn { bottom: 4.75rem; }
        #jfl-chat-panel { position: fixed; right: 1rem; bottom: 1rem; z-index: 46; width: 24rem; max-width: calc(100vw - 2rem); height: min(40rem, calc(100vh - 6.5rem)); display: flex; flex-direction: column; overflow: hidden; border-radius: 1rem; border: 1px solid var(--color-steel-200); background: #fff; box-shadow: 0 16px 48px rgb(0 0 0 / .25); }
        #jfl-chat-panel[hidden] { display: none; }
        .jc-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .4rem .7rem; background: var(--color-steel-900); color: #fff; }
        .jc-head p { margin: 0; font-weight: 700; line-height: 1.2; }
        .jc-head small { display: block; font-weight: 400; font-size: .78rem; color: var(--color-steel-200); }
        .jc-head .jc-icon { width: 2.25rem; height: 2.25rem; }
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
        <img src="/images/buckny.svg" alt="" width="40" height="40" class="jc-face">
        <span class="jc-label">Ask Buckny</span>
    </button>
    <span class="jc-say" aria-hidden="true">Hello, I’m Buckny. How may I help you today?</span>
    <section id="jfl-chat-panel" role="dialog" aria-label="Buckny, the JFL Solar chat assistant" hidden>
        <div class="jc-head">
            <img src="/images/buckny.svg" alt="" width="40" height="40" class="jc-face jc-face-head" title="Click me to say hello">
            <p style="margin-right: auto">Buckny<small>Happy to serve!</small></p>
            <span class="jc-say jc-say-head" aria-hidden="true">Hello, I’m Buckny. How may I help you today?</span>
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
        <p class="jc-foot">Answers are by AI and may be wrong — Sales confirms prices and stock. Chats are saved for 90 days. Don’t share card numbers or passwords. <a href="/privacy#chat">Privacy</a></p>
    </section>
    <script>
        (() => {
            const root = document.getElementById('jfl-chat');
            if (!root || !window.fetch) return;
            root.hidden = false;
            const btn = root.querySelector('#jfl-chat-btn'), panel = root.querySelector('#jfl-chat-panel'), log = root.querySelector('[data-chat-log]');
            const form = root.querySelector('[data-chat-form]'), input = root.querySelector('#jfl-chat-input'), send = root.querySelector('.jc-send');
            const KEY = 'jfl-chat-v1';
            const greeting = 'Hello, I’m Buckny, the JFL Solar assistant. How may I help you today? Ask me about our solar panels, inverters, batteries, packages, prices, delivery and payment. Puwede rin tayong mag-usap sa Filipino! 😊';
            const starters = ['How much is the 6kW Trio Package?', 'Which battery should I choose?', 'How do I order and pick up?', 'Talk to a person', 'Mag-Filipino tayo'];
            let messages = [], busy = false;
            try { const saved = JSON.parse(sessionStorage.getItem(KEY) || '[]'); if (Array.isArray(saved)) messages = saved.filter((m) => m && (m.role === 'user' || m.role === 'assistant') && typeof m.content === 'string').slice(-24); } catch (e) {}
            const save = () => { try { sessionStorage.setItem(KEY, JSON.stringify(messages.slice(-24))); } catch (e) {} };
            const newId = () => (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : Date.now().toString(36) + Math.random().toString(36).slice(2, 12);
            let cid = ''; try { cid = sessionStorage.getItem(KEY + '-id') || ''; } catch (e) {}
            const setId = (id) => { cid = id; try { sessionStorage.setItem(KEY + '-id', id); } catch (e) {} };
            if (!/^[A-Za-z0-9-]{8,64}$/.test(cid) || !messages.length) setId(newId());

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
            // Scroll so the visitor's question sits at the top, with the start of Buckny's reply below it.
            const showFrom = (el) => { if (el) log.scrollTop += el.getBoundingClientRect().top - log.getBoundingClientRect().top - 8; };
            const note = (text) => { const p = document.createElement('p'); p.className = 'jc-note'; p.textContent = text; log.append(p); log.scrollTop = log.scrollHeight; };
            const draw = () => {
                log.textContent = '';
                bubble('assistant', greeting);
                if (!messages.length) {
                    const chips = document.createElement('div'); chips.className = 'jc-chips';
                    starters.forEach((s) => { const c = document.createElement('button'); c.type = 'button'; c.className = 'jc-chip'; c.textContent = s; c.addEventListener('click', () => ask(s)); chips.append(c); });
                    log.append(chips);
                }
                let lastQ = null;
                messages.forEach((m) => { const d = bubble(m.role, m.content); if (m.role === 'user') lastQ = d; });
                showFrom(lastQ);
            };
            // Buckny says hello (speech bubble + talking face) when the chat opens or his face is clicked.
            const sayBubble = root.querySelector('.jc-say-head'); let sayTimer = 0;
            // His voice: the browser's own speech engine (no sound file to download). Browsers only allow it after a click,
            // so he speaks the first time the chat is opened in a visit and whenever his face is clicked.
            const HELLO = 'Hello, I’m Buckny. How may I help you today?';
            const speak = () => {
                try {
                    const synth = window.speechSynthesis; if (!synth) return;
                    synth.cancel();
                    const u = new SpeechSynthesisUtterance(HELLO.replace('’', "'"));
                    const voices = synth.getVoices().filter((v) => /^en[-_]/i.test(v.lang));
                    const pick = ['Google US English', 'Samantha', 'Microsoft Aria', 'Microsoft Jenny', 'Microsoft Guy', 'Karen', 'Daniel'];
                    u.voice = pick.map((n) => voices.find((v) => v.name.includes(n))).find(Boolean) || voices.find((v) => /en[-_]US/i.test(v.lang)) || voices[0] || null;
                    u.lang = u.voice ? u.voice.lang : 'en-US'; u.pitch = 1.35; u.rate = 1.02; u.volume = 1;
                    synth.speak(u);
                } catch (e) {}
            };
            if (window.speechSynthesis) { window.speechSynthesis.getVoices(); window.speechSynthesis.addEventListener?.('voiceschanged', () => window.speechSynthesis.getVoices()); }
            const say = (voice) => {
                if (voice) speak();
                clearTimeout(sayTimer); panel.classList.remove('is-saying'); void panel.offsetWidth;
                panel.classList.add('is-saying'); sayBubble.classList.add('is-on');
                sayTimer = setTimeout(() => { panel.classList.remove('is-saying'); sayBubble.classList.remove('is-on'); }, 2600);
            };
            root.querySelector('.jc-face-head').addEventListener('click', () => say(true));
            const open = (yes) => {
                panel.hidden = !yes; btn.hidden = yes; btn.setAttribute('aria-expanded', String(yes));
                if (yes) { draw(); setTimeout(() => input.focus(), 30); let first = true; try { first = !sessionStorage.getItem(KEY + '-hello'); sessionStorage.setItem(KEY + '-hello', '1'); } catch (e) {} say(first); } else { btn.focus(); try { window.speechSynthesis?.cancel(); } catch (e) {} }
            };
            const ask = async (text) => {
                text = text.trim();
                if (!text || busy) return;
                busy = true; send.disabled = true; panel.classList.add('is-thinking');
                root.querySelector('.jc-chips')?.remove();
                messages.push({ role: 'user', content: text }); save();
                const question = bubble('user', text);
                const typing = document.createElement('div'); typing.className = 'jc-msg jc-bot'; typing.innerHTML = '<span class="jc-dots" aria-label="Typing"><i></i><i></i><i></i></span>'; log.append(typing); log.scrollTop = log.scrollHeight;
                try {
                    const r = await fetch('/chat', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf }, body: JSON.stringify({ messages, cid, page: location.pathname }) });
                    typing.remove();
                    if (r.status === 419) { messages.pop(); save(); note('This page has been open a while. Please reload the page and ask again.'); return; }
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.reply) { messages.pop(); save(); note(data.error || 'Sorry, something went wrong. Please try again, or call {{ $chatPhone }}.'); return; }
                    messages.push({ role: 'assistant', content: data.reply }); save();
                    bubble('assistant', data.reply);
                    showFrom(question);
                } catch (e) {
                    typing.remove(); messages.pop(); save();
                    note('You seem to be offline. Please check your connection and try again.');
                } finally { busy = false; send.disabled = false; panel.classList.remove('is-thinking'); input.focus(); }
            };
            btn.addEventListener('click', () => open(true));
            root.querySelector('[data-chat-close]').addEventListener('click', () => open(false));
            root.querySelector('[data-chat-reset]').addEventListener('click', () => { messages = []; save(); setId(newId()); draw(); input.focus(); });
            panel.addEventListener('keydown', (e) => { if (e.key === 'Escape') open(false); });
            form.addEventListener('submit', (e) => { e.preventDefault(); const t = input.value; input.value = ''; input.style.height = ''; ask(t); });
            input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
            input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 112) + 'px'; });
        })();
    </script>
</div>
