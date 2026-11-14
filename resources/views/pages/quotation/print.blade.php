{{-- Printable "Make a Quotation" list (opens on its own page). Needs: $q, $sum, $member, $settings. --}}
@php
    use App\Support\Pricing;
    use App\Support\Quotations;
    use App\Support\Site;
    $th = 'text-align:left;padding:8px 10px;border-bottom:2px solid #1f2937;font-size:13px;color:#374151;';
    $td = 'padding:8px 10px;border-bottom:1px solid #d1d5db;vertical-align:top;font-size:14px;color:#111827;';
    $label = 'padding:3px 12px 3px 0;color:#4b5563;font-size:14px;vertical-align:top;white-space:nowrap;';
    $value = 'padding:3px 0;color:#111827;font-size:14px;vertical-align:top;';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $q->reference() }} {{ $q->title }} — {{ Site::NAME }}</title>
    <link rel="icon" href="/brand/jfl-symbol-96.png">
    <style>
        body { margin: 0; background: #f3f4f6; }
        .sheet { background: #fff; max-width: 820px; margin: 24px auto; padding: 32px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
        .bar { max-width: 820px; margin: 16px auto 0; padding: 0 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; font-family: Arial, Helvetica, sans-serif; }
        .btn { font: bold 15px Arial, Helvetica, sans-serif; padding: 10px 18px; border-radius: 6px; border: 1px solid #111827; background: #111827; color: #fff; cursor: pointer; text-decoration: none; }
        .btn.alt { background: #fff; color: #111827; }
        .num { text-align: right !important; white-space: nowrap; }
        @media (max-width: 640px) { .sheet { margin: 12px; padding: 16px; } .foot td { display: block; width: auto !important; padding-right: 0 !important; } }
        .foot { break-inside: avoid; page-break-inside: avoid; }
        @media print { @page { size: A4; margin: 14mm; } body { background: #fff; } .no-print { display: none !important; } .sheet { box-shadow: none; margin: 0; padding: 0; max-width: none; } }
    </style>
</head>
<body>
    <div class="bar no-print">
        <button type="button" class="btn" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn alt" href="/quotation/{{ $q->id }}">Back to your quotation</a>
    </div>
    <main class="sheet">
        <div style="font-family:Arial,Helvetica,sans-serif;color:#111827;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border-bottom:3px solid #f59e0b;">
                <tr>
                    {{-- Logo only; the company's addresses and contact details are in the footer. --}}
                    <td style="padding:0 0 12px;vertical-align:middle;"><img src="/brand/jfl-renewables-logo-272.png" width="190" height="56" alt="{{ Site::NAME }}" style="display:block;width:190px;height:auto;border:0;"></td>
                </tr>
            </table>

            <h1 style="margin:18px 0 2px;font-size:22px;">Quotation request</h1>
            <p style="margin:0 0 12px;font-size:16px;font-weight:bold;">{{ $q->title }}</p>
            <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr><td style="{{ $label }}">Reference</td><td style="{{ $value }}"><strong style="font-family:Consolas,Menlo,monospace;">{{ $q->reference() }}</strong></td></tr>
                <tr><td style="{{ $label }}">Date</td><td style="{{ $value }}">{{ now()->timezone('Asia/Manila')->format('j F Y') }}</td></tr>
                <tr><td style="{{ $label }}">Prepared by</td><td style="{{ $value }}">{{ $member->name }} · {{ $member->email }}@if ($q->phone) · {{ $q->phone }}@endif</td></tr>
                @if ($q->location)<tr><td style="{{ $label }}">Location</td><td style="{{ $value }}">{{ $q->location }}</td></tr>@endif
                <tr><td style="{{ $label }}">Status</td><td style="{{ $value }}">{{ $q->sent_at ? 'Sent to JFL Solar on '.$q->sent_at->timezone('Asia/Manila')->format('j F Y').($q->edited_after_send ? ' (changed since)' : '') : 'Draft — not sent yet' }}</td></tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:18px;">
                <thead><tr><th style="{{ $th }}">#</th><th style="{{ $th }}">Item</th><th class="num" style="{{ $th }}">Qty</th><th class="num" style="{{ $th }}">Website price</th><th class="num" style="{{ $th }}">Total</th></tr></thead>
                <tbody>
                    @forelse ($sum['lines'] as $n => $l)
                        @if ($n === 0 || $sum['lines'][$n - 1]['category'] !== $l['category'])
                            <tr><th colspan="5" style="text-align:left;padding:14px 10px 4px;border-bottom:1px solid #9ca3af;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#111827;">{{ $l['category'] }}</th></tr>
                        @endif
                        <tr>
                            <td style="{{ $td }}">{{ $n + 1 }}</td>
                            <td style="{{ $td }}"><strong>{{ $l['name'] }}</strong>@if (Quotations::brandModel($l) !== '')<br><span style="font-size:12px;color:#374151;">{{ Quotations::brandModel($l) }}</span>@endif @if ($l['unavailable'])<br><span style="font-size:12px;color:#92400e;">Currently not available</span>@endif @if ($l['kind'] === 'custom')<br><span style="font-size:12px;color:#4b5563;">Own item</span>@endif</td>
                            <td class="num" style="{{ $td }}">{{ $l['qty'] }} {{ $l['unit'] }}</td>
                            <td class="num" style="{{ $td }}">{{ $l['price'] !== null ? Pricing::formatPhp($l['price']) : 'To be quoted' }}</td>
                            <td class="num" style="{{ $td }}">{{ $l['total'] !== null ? Pricing::formatPhp($l['total']) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="{{ $td }}">No items yet.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr><td colspan="4" class="num" style="padding:10px;font-size:15px;font-weight:bold;">Estimated total</td><td class="num" style="padding:10px;font-size:16px;font-weight:bold;">{{ Pricing::formatPhp($sum['total']) }}</td></tr>
                    @if ($sum['unpriced'])<tr><td colspan="5" class="num" style="padding:0 10px 6px;font-size:13px;color:#4b5563;">+ {{ $sum['unpriced'] }} {{ $sum['unpriced'] === 1 ? 'item' : 'items' }} to be quoted by JFL Solar</td></tr>@endif
                </tfoot>
            </table>

            @if (trim((string) $q->notes) !== '')
                <h2 style="margin:22px 0 6px;font-size:16px;">Notes</h2>
                <p style="margin:0;font-size:14px;white-space:pre-line;">{{ $q->notes }}</p>
            @endif
            <p style="margin:24px 0 0;padding-top:10px;border-top:1px solid #d1d5db;font-size:12px;color:#4b5563;line-height:1.5;">
                Prepared with {{ Quotations::NAME }} on {{ preg_replace('#^https?://|/$#', '', Site::publicUrl()) }}. Prices are JFL Solar's website prices on the date above, for reference only;
                this is not a formal quotation. Final prices, availability and delivery are confirmed by JFL Solar in writing.
            </p>

            {{-- Footer: where to find us and how to reach us (same details as the website footer, from Admin → Settings). --}}
            @php
                $footHead = 'margin:0 0 4px;font-size:11px;font-weight:bold;letter-spacing:.06em;text-transform:uppercase;color:#111827;';
                $footText = 'margin:0;font-size:12px;line-height:1.5;color:#374151;';
                $channels = \App\Support\Contact::channels($settings);
            @endphp
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="foot" style="border-collapse:collapse;margin-top:16px;border-top:3px solid #f59e0b;">
                <tr>
                    @if ($settings->office_address)
                        <td style="padding:12px 14px 0 0;vertical-align:top;width:33%;">
                            <p style="{{ $footHead }}">Office</p>
                            <p style="{{ $footText }}">{{ $settings->office_address }}</p>
                        </td>
                    @endif
                    @if ($settings->warehouse_address)
                        <td style="padding:12px 14px 0 0;vertical-align:top;width:33%;">
                            <p style="{{ $footHead }}">Warehouse</p>
                            <p style="{{ $footText }}">{{ $settings->warehouse_address }}</p>
                        </td>
                    @endif
                    <td style="padding:12px 0 0;vertical-align:top;">
                        <p style="{{ $footHead }}">Contact</p>
                        @foreach ($channels as $c)
                            <p style="{{ $footText }}"><span style="color:#6b7280;">{{ $c['label'] }}:</span> {{ $c['detail'] }}</p>
                        @endforeach
                        <p style="{{ $footText }}"><span style="color:#6b7280;">Website:</span> {{ preg_replace('#^https?://|/$#', '', Site::publicUrl()) }}</p>
                    </td>
                </tr>
            </table>
        </div>
    </main>
</body>
</html>
