<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\Pricing;
use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Alert to the business inbox: an online order was paid, or a pay-at-pickup order was placed. */
class NewOrder extends Mailable
{
    public function __construct(public Order $order, public bool $preview = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: mb_substr(($this->preview ? '[Staff preview — not a real order] ' : '').($this->order->payment_mode === 'in_person' && ! $this->order->paid_at ? 'New order (pay at pickup, '.(\App\Support\Shop::LOCATION_NAMES[$this->order->pickup_location] ?? 'Office').')' : 'Paid online order')
                ." {$this->order->reference} — ".Pricing::formatPhp($this->order->total_php)." ({$this->order->customer_name})", 0, 200),
            replyTo: [new Address($this->order->customer_email, $this->order->customer_name)],
        );
    }

    public function content(): Content
    {
        $o = $this->order;
        $lines = $o->items->map(fn ($i) => "{$i->quantity} {$i->unitLabel()} × {$i->name} @ ".Pricing::formatPhp($i->unit_price_php).' = '.Pricing::formatPhp($i->line_total_php))->all();

        return new Content(html: 'mail.new-order-html', text: 'mail.new-order', with: [
            'order' => $o,
            'settings' => \App\Support\PublicContent::settings(),
            'preview' => $this->preview,
            'printUrl' => $this->preview ? null : Site::publicUrl($o->printPath()),
            'rows' => [
                'Order' => $o->reference,
                'Placed' => ($o->created_at ?? now())->copy()->setTimezone('Asia/Manila')->format('M j, Y g:i A'),
                'Name' => $o->customer_name,
                'Phone' => $o->customer_phone,
                'Email' => $o->customer_email,
                'Total' => Pricing::formatPhp($o->total_php),
                'Payment' => $o->paid_at
                    ? 'Paid '.Pricing::formatPhp($o->amount_paid_php ?? $o->total_php).($o->payment_method ? ' ('.\App\Support\Shop::methodName($o->payment_method).')' : '')
                    : 'Pays at pickup'.($o->payment_preference ? ' by '.\App\Support\Shop::choiceLabel($o->payment_preference) : '').' — not paid yet',
                'Pickup at' => (\App\Support\Shop::LOCATION_NAMES[$o->pickup_location] ?? 'Office').' — '.\App\Support\Shop::pickupAddress(\App\Support\PublicContent::settings(), $o->pickup_location),
                'Directions' => 'search "'.\App\Support\Shop::MAPS_NAME.'" on Google Maps or Waze',
            ],
            'lines' => $lines,
            'notes' => $o->customer_notes,
            'sendDetails' => ! $o->paid_at ? ['gcash' => 'GCash number', 'bank_transfer' => 'bank details'][$o->payment_preference] ?? null : null,
            'site' => Site::NAME,
            'adminUrl' => $o->id ? Site::publicUrl('/admin/orders/'.$o->id) : null,
        ]);
    }
}
