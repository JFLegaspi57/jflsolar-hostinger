{{--
    Full order summary: everything the customer filled in, the items and the total.
    Inline styles only, so the same markup works on the printable page and in email.
    Needs: $o (Order with items; may be an unsaved staff-preview order), $settings.
--}}
@php
    use App\Support\Pricing;
    use App\Support\Shop;
    use App\Support\Site;
    $placed = ($o->created_at ?? now())->copy()->setTimezone('Asia/Manila');
    $place = Shop::LOCATION_NAMES[$o->pickup_location] ?? 'Warehouse';
    $maps = Shop::directionLinks();
    $payment = match (true) {
        (bool) $o->paid_at => 'Paid '.Pricing::formatPhp($o->amount_paid_php ?? $o->total_php).($o->payment_method ? ' by '.Shop::methodName($o->payment_method) : ''),
        $o->payment_mode === 'online' => 'Online (PayMongo) — awaiting payment',
        default => 'Pay at pickup'.($o->payment_preference ? ' by '.Shop::choiceLabel($o->payment_preference) : '').' — not paid yet',
    };
    $th = 'text-align:left;padding:8px 10px;border-bottom:2px solid #1f2937;font-size:13px;color:#374151;';
    $td = 'padding:8px 10px;border-bottom:1px solid #d1d5db;vertical-align:top;font-size:14px;color:#111827;';
    $label = 'padding:4px 12px 4px 0;color:#4b5563;font-size:14px;vertical-align:top;white-space:nowrap;';
    $value = 'padding:4px 0;color:#111827;font-size:14px;vertical-align:top;';
    $h2 = 'margin:22px 0 6px;font-size:16px;color:#111827;';
@endphp
<div style="font-family:Arial,Helvetica,sans-serif;color:#111827;max-width:760px;margin:0 auto;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border-bottom:3px solid #f59e0b;">
        <tr>
            <td style="padding:0 0 12px;vertical-align:middle;">
                <img src="{{ Site::publicUrl('/brand/jfl-renewables-logo-272.png') }}" width="190" height="56" alt="{{ Site::NAME }}" style="display:block;width:190px;height:auto;border:0;">
            </td>
            <td style="padding:0 0 12px;text-align:right;vertical-align:middle;font-size:13px;color:#374151;line-height:1.5;">
                <strong style="font-size:15px;color:#111827;">{{ Site::NAME }}</strong><br>
                {{ $settings->phone_display ?? $settings->phone_e164 }}@if ($settings->public_email)<br>{{ $settings->public_email }}@endif<br>
                {{ preg_replace('#^https?://|/$#', '', Site::publicUrl()) }}
            </td>
        </tr>
    </table>

    <h1 style="margin:18px 0 4px;font-size:22px;color:#111827;">Order summary</h1>
    <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr><td style="{{ $label }}">Order number</td><td style="{{ $value }}"><strong style="font-family:Consolas,Menlo,monospace;font-size:16px;">{{ $o->reference }}</strong></td></tr>
        <tr><td style="{{ $label }}">Placed</td><td style="{{ $value }}">{{ $placed->format('F j, Y, g:i A') }} (Philippine time)</td></tr>
        <tr><td style="{{ $label }}">Status</td><td style="{{ $value }}">{{ $o->statusLabel() }}</td></tr>
    </table>

    <h2 style="{{ $h2 }}">Customer</h2>
    <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr><td style="{{ $label }}">Name</td><td style="{{ $value }}">{{ $o->customer_name }}</td></tr>
        <tr><td style="{{ $label }}">Mobile / phone</td><td style="{{ $value }}">{{ $o->customer_phone }}</td></tr>
        <tr><td style="{{ $label }}">Email</td><td style="{{ $value }}">{{ $o->customer_email }}</td></tr>
        <tr><td style="{{ $label }}">Notes</td><td style="{{ $value }}">{!! $o->customer_notes ? nl2br(e($o->customer_notes)) : '<span style="color:#6b7280;">None</span>' !!}</td></tr>
    </table>

    <h2 style="{{ $h2 }}">Payment and pickup</h2>
    <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr><td style="{{ $label }}">Payment</td><td style="{{ $value }}">{{ $payment }}</td></tr>
        <tr><td style="{{ $label }}">Pickup at</td><td style="{{ $value }}">Our {{ strtolower($place) }} — {{ Shop::pickupAddress($settings, $o->pickup_location) }}</td></tr>
        <tr><td style="{{ $label }}">Directions</td><td style="{{ $value }}">Search “{{ Shop::MAPS_NAME }}” on <a href="{{ $maps['google'] }}" style="color:#1d4ed8;">Google Maps</a> or <a href="{{ $maps['waze'] }}" style="color:#1d4ed8;">Waze</a></td></tr>
        <tr><td style="{{ $label }}">Delivery</td><td style="{{ $value }}">Not included (pickup only)</td></tr>
    </table>

    <h2 style="{{ $h2 }}">Items</h2>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <thead>
            <tr>
                <th style="{{ $th }}">Item</th>
                <th style="{{ $th }}text-align:right;">Qty</th>
                <th style="{{ $th }}text-align:right;">Unit price</th>
                <th style="{{ $th }}text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($o->items as $i)
                <tr>
                    <td style="{{ $td }}">{{ $i->name }}@if ($i->brand || $i->model)<br><span style="color:#4b5563;font-size:12px;">{{ trim(($i->brand ?? '').' '.($i->model ?? '')) }}</span>@endif</td>
                    <td style="{{ $td }}text-align:right;white-space:nowrap;">{{ $i->quantity }} {{ $i->unitLabel() }}</td>
                    <td style="{{ $td }}text-align:right;white-space:nowrap;">{{ Pricing::formatPhp($i->unit_price_php) }}</td>
                    <td style="{{ $td }}text-align:right;white-space:nowrap;">{{ Pricing::formatPhp($i->line_total_php) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="padding:10px;text-align:right;font-size:16px;font-weight:bold;border-top:2px solid #1f2937;">Total</td>
                <td style="padding:10px;text-align:right;font-size:16px;font-weight:bold;border-top:2px solid #1f2937;white-space:nowrap;">{{ Pricing::formatPhp($o->total_php) }}</td>
            </tr>
        </tfoot>
    </table>

    <p style="margin:20px 0 0;padding:10px 12px;border:1px solid #f0d39a;background:#fffbeb;color:#92400e;font-size:14px;line-height:1.5;">
        <strong>Please call our Sales team before pickup</strong> at {{ $settings->phone_display ?? $settings->phone_e164 }} and mention order {{ $o->reference }}, so we can confirm your order is ready. Bring this summary or your order number.
    </p>
    <p style="margin:14px 0 0;color:#4b5563;font-size:12px;line-height:1.5;">
        Prices are pickup prices in Philippine pesos at the time of ordering. The customer confirmed they will pick up the items and agreed to the website terms.
        This summary is not an official receipt.
    </p>
</div>
