{{-- Printable order summary (opens in its own window). Needs: $order, $settings, $backUrl, $preview. --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Order {{ $order->reference }} — {{ \App\Support\Site::NAME }}</title>
    <link rel="icon" href="/brand/jfl-symbol-96.png">
    <style>
        body { margin: 0; background: #f3f4f6; }
        .sheet { background: #fff; max-width: 820px; margin: 24px auto; padding: 32px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
        .bar { max-width: 820px; margin: 16px auto 0; padding: 0 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; font-family: Arial, Helvetica, sans-serif; }
        .btn { font: bold 15px Arial, Helvetica, sans-serif; padding: 10px 18px; border-radius: 6px; border: 1px solid #111827; background: #111827; color: #fff; cursor: pointer; text-decoration: none; }
        .btn.alt { background: #fff; color: #111827; }
        .preview { max-width: 820px; margin: 12px auto 0; padding: 10px 14px; background: #ecfdf5; border: 1px solid #b7e2c6; color: #065f46; font: 14px/1.5 Arial, Helvetica, sans-serif; }
        @media (max-width: 640px) { .sheet { margin: 12px; padding: 16px; } }
        @media print {
            @page { size: A4; margin: 14mm; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet { box-shadow: none; margin: 0; padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
    @if ($preview)
        <p class="preview no-print"><strong>Staff preview</strong> — no order was saved and nothing was charged. This is the summary customers can print; a copy was sent to the staff email.</p>
    @endif
    <div class="bar no-print">
        <button type="button" class="btn" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn alt" href="{{ $backUrl }}">Back to {{ $preview ? 'checkout' : 'your order' }}</a>
    </div>
    <main class="sheet">
        @include('partials.order-summary', ['o' => $order, 'settings' => $settings])
    </main>
</body>
</html>
