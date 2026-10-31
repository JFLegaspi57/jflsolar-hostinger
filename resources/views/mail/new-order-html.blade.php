{{-- HTML email to staff: the same summary the customer can print, plus staff links. --}}
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Order {{ $order->reference }}</title></head>
<body style="margin:0;padding:16px;background:#ffffff;">
    @if ($preview)
        <p style="font-family:Arial,Helvetica,sans-serif;max-width:760px;margin:0 auto 14px;padding:10px 12px;background:#ecfdf5;border:1px solid #b7e2c6;color:#065f46;font-size:14px;">
            <strong>Staff preview — not a real order.</strong> No order was saved. This is the copy staff receive for each new order.
        </p>
    @endif
    @include('partials.order-summary', ['o' => $order, 'settings' => $settings])
    <div style="font-family:Arial,Helvetica,sans-serif;max-width:760px;margin:20px auto 0;padding-top:12px;border-top:1px solid #d1d5db;font-size:14px;color:#111827;line-height:1.6;">
        @if ($sendDetails)
            <p style="margin:0 0 8px;"><strong>To do:</strong> send the customer your {{ $sendDetails }} when you confirm the order.</p>
        @endif
        <p style="margin:0;">Reply to this email to write to the customer.</p>
        @if ($printUrl)<p style="margin:6px 0 0;"><a href="{{ $printUrl }}" style="color:#1d4ed8;">Open the printable summary</a></p>@endif
        @if ($adminUrl)<p style="margin:6px 0 0;"><a href="{{ $adminUrl }}" style="color:#1d4ed8;">Open this order in the website admin</a></p>@endif
        <p style="margin:12px 0 0;color:#6b7280;font-size:12px;">Sent from the {{ $site }} online shop</p>
    </div>
</body>
</html>
