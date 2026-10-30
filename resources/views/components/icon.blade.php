@props(['name', 'label' => null])
@php
    // Minimal stroke icon set (24px grid). Decorative unless a label is given.
    $paths = [
        'phone' => 'M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2',
        'chat' => 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z',
        'mail' => 'M4 6h16v12H4zM4 7l8 6 8-6',
        // Line-style messaging marks: round bubble + handset (WhatsApp), rounded bubble + call waves (Viber), bubble + zigzag (Messenger).
        'whatsapp' => 'M3.5 20.5l1.3-4.3A8.5 8.5 0 1 1 7.9 19.3ZM9 8.2c-.2 3.6 3.2 7 6.8 6.8l.7-1.7-2-1-1 .9A5 5 0 0 1 10.9 10.6l.9-1-1-2Z',
        'viber' => 'M12 3.2c5 0 8 1.6 8 6.8s-3 6.8-8 6.8l-3 3.1v-3.4C5.6 15.6 4 13.5 4 10c0-5.2 3-6.8 8-6.8ZM8.6 7.6c.3 2.9 2.4 5 5.3 5.3l.9-1.1-1.5-.9-.7.6A3.5 3.5 0 0 1 10.6 9.4l.6-.7-.9-1.5ZM13 6.5a3.5 3.5 0 0 1 3.5 3.5M13 8.4a1.6 1.6 0 0 1 1.6 1.6',
        'messenger' => 'M12 3c5 0 9 3.7 9 8.4s-4 8.4-9 8.4a9.8 9.8 0 0 1-2.6-.4L6.5 21v-3.2A8.2 8.2 0 0 1 3 11.4C3 6.7 7 3 12 3ZM7.5 13.8l3.3-3.6 2.6 2 3.1-3.6-3.3 3.6-2.6-2',
        'pin' => 'M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Zm0-8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z',
        'arrow' => 'M5 12h14M13 6l6 6-6 6',
        'check' => 'M5 12.5l4.5 4.5L19 7.5',
        'x' => 'M6 6l12 12M18 6 6 18',
        'menu' => 'M4 7h16M4 12h16M4 17h16',
        'search' => 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Zm5-2 4 4',
        'facebook' => 'M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8Z',
        'warehouse' => 'M3 21V9l9-5 9 5v12M7 21v-7h10v7M7 17h10',
        'building' => 'M5 21V4h10v17M15 9h4v12M8 8h1M11 8h1M8 12h1M11 12h1M8 16h1M11 16h1M3 21h18',
        'sun' => 'M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4',
        'bolt' => 'M13 2 4 14h7l-1 8 9-12h-7l1-8Z',
        'battery' => 'M3 8h15v8H3zM18 11h3v2h-3M6 11v2M9 11v2',
        'tools' => 'M14.7 6.3a4 4 0 0 0 5 5L21 13l-8 8-3-3 8-8M3 21l6-6M9.3 6.3 6 3 3 6l3.3 3.3',
        'file' => 'M14 3H6v18h12V7l-4-4Zm0 0v4h4',
        'external' => 'M14 4h6v6M20 4l-9 9M18 14v6H4V6h6',
    ];
@endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" focusable="false"
    {{ $attributes->merge(['class' => 'shrink-0 size-5']) }}
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif><path d="{{ $paths[$name] }}" /></svg>
