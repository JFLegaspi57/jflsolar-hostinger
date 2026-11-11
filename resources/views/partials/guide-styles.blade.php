{{-- Shared styles of the technical guides (/hybrid-pv-system-guide, /grid-tied-pv-system-guide). --}}
<style>
    .hg h2 { margin-bottom: .5rem; font-size: 1.5rem; font-weight: 800; color: var(--color-steel-950); scroll-margin-top: 6rem; }
    .hg h3 { margin: 1.25rem 0 .25rem; font-size: 1.125rem; font-weight: 700; color: var(--color-steel-950); }
    .hg p + p { margin-top: .75rem; }
    .hg ul.dots li { margin-left: 1.25rem; list-style: disc; }
    .hg ul.dots li + li { margin-top: .35rem; }
    .hg ol.steps { counter-reset: s; }
    .hg ol.steps > li { counter-increment: s; position: relative; padding-left: 2.6rem; }
    .hg ol.steps > li + li { margin-top: .9rem; }
    .hg ol.steps > li::before { content: counter(s); position: absolute; left: 0; top: 0; width: 1.9rem; height: 1.9rem; border-radius: 999px; background: var(--color-gold-500); color: var(--color-steel-950); font-weight: 800; display: grid; place-items: center; }
    .hg a.inline { font-weight: 600; color: var(--color-steel-900); text-decoration: underline; }
    .hg a.inline:hover { color: var(--color-gold-800); }
    .hg-note { border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); background: var(--color-steel-50); padding: 1.1rem 1.25rem; }
    .hg-formula { margin-top: .75rem; border-left: 4px solid var(--color-gold-500); background: #fff; padding: .7rem 1rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .9rem; color: var(--color-steel-950); overflow-x: auto; white-space: nowrap; }
    .hg-table-wrap { overflow-x: auto; border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); }
    .hg-table { width: 100%; min-width: 36rem; border-collapse: collapse; font-size: .92rem; background: #fff; }
    .hg-table th, .hg-table td { padding: .6rem .75rem; text-align: left; vertical-align: top; border-bottom: 1px solid var(--color-steel-200); }
    .hg-table thead th { background: var(--color-steel-50); font-size: .75rem; letter-spacing: .03em; text-transform: uppercase; color: var(--color-steel-700); }
    .hg-table tbody tr:last-child > * { border-bottom: 0; }
    .hg-table tbody th { font-weight: 700; color: var(--color-steel-950); }
    .hg-toc a { display: inline-flex; min-height: 2.75rem; align-items: center; font-weight: 600; color: var(--color-steel-900); text-decoration: underline; }
    .hg-diagram { border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); background: #fff; padding: .75rem; overflow-x: auto; }
    .hg-diagram svg { display: block; width: 100%; min-width: 620px; height: auto; }
    .hg-diagram .box { fill: var(--color-steel-50); stroke: var(--color-steel-300); stroke-width: 1.5; }
    .hg-diagram .box-main { fill: #fff; stroke: var(--color-steel-900); stroke-width: 2; }
    .hg-diagram .inner { fill: var(--color-gold-50); stroke: var(--color-gold-500); stroke-width: 1.2; }
    .hg-diagram .t { fill: var(--color-steel-950); font: 700 14px system-ui, sans-serif; }
    .hg-diagram .s { fill: var(--color-steel-700); font: 400 11.5px system-ui, sans-serif; }
    .hg-diagram .dc { stroke: #c2410c; stroke-width: 3; fill: none; }
    .hg-diagram .ac { stroke: #1d4ed8; stroke-width: 3; fill: none; }
    .hg-diagram .data { stroke: var(--color-steel-500); stroke-width: 1.5; stroke-dasharray: 5 4; fill: none; }
    .hg-legend { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: .5rem; font-size: .85rem; color: var(--color-steel-700); }
    .hg-legend span::before { content: ''; display: inline-block; width: 1.6rem; height: 3px; margin-right: .4rem; vertical-align: middle; background: currentColor; }
    .hg-legend .l-dc { color: #c2410c; } .hg-legend .l-ac { color: #1d4ed8; } .hg-legend .l-data { color: var(--color-steel-500); }
    .hg-legend .l-dc, .hg-legend .l-ac, .hg-legend .l-data { font-weight: 600; }
</style>
