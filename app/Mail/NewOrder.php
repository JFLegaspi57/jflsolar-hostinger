<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\Pricing;
use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Alert to the business inbox when an online order has been paid. */
class NewOrder extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: mb_substr("Paid online order {$this->order->reference} — ".Pricing::formatPhp($this->order->total_php)." ({$this->order->customer_name})", 0, 200),
            replyTo: [new Address($this->order->customer_email, $this->order->customer_name)],
        );
    }

    public function content(): Content
    {
        $o = $this->order;
        $lines = $o->items->map(fn ($i) => "{$i->quantity} {$i->unitLabel()} × {$i->name} @ ".Pricing::formatPhp($i->unit_price_php).' = '.Pricing::formatPhp($i->line_total_php))->all();

        return new Content(text: 'mail.new-order', with: [
            'rows' => [
                'Order' => $o->reference,
                'Name' => $o->customer_name,
                'Phone' => $o->customer_phone,
                'Email' => $o->customer_email,
                'Total paid' => Pricing::formatPhp($o->amount_paid_php ?? $o->total_php).($o->payment_method ? ' ('.\App\Support\Shop::methodName($o->payment_method).')' : ''),
                'Fulfilment' => 'Pickup',
            ],
            'lines' => $lines,
            'notes' => $o->customer_notes,
            'site' => Site::NAME,
            'adminUrl' => Site::url('/admin/orders/'.$o->id),
        ]);
    }
}
