<?php

namespace App\Mail;

use App\Models\Inquiry;
use App\Support\Labels;
use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Alert to the business inbox. Reply goes straight to the customer when they gave an email. */
class NewInquiry extends Mailable
{
    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        $type = Labels::INQUIRY_TYPES[$this->inquiry->inquiry_type] ?? $this->inquiry->inquiry_type;
        $item = $this->inquiry->item_label ? ' — '.$this->inquiry->item_label : '';

        return new Envelope(
            subject: mb_substr("Website inquiry: {$type}{$item} ({$this->inquiry->name})", 0, 200),
            replyTo: $this->inquiry->email ? [new Address($this->inquiry->email, $this->inquiry->name)] : [],
        );
    }

    public function content(): Content
    {
        $i = $this->inquiry;
        $rows = array_filter([
            'Name' => $i->name,
            'Phone' => $i->phone ?: '—',
            'Email' => $i->email ?: '—',
            'Inquiry type' => Labels::INQUIRY_TYPES[$i->inquiry_type] ?? $i->inquiry_type,
            'Selected item' => $i->item_label,
            'Location' => $i->location ?: '—',
            'Monthly bill' => $i->bill_range ? (Labels::BILL_RANGES[$i->bill_range] ?? null) : null,
        ], fn ($v) => $v !== null);

        return new Content(text: 'mail.new-inquiry', with: ['rows' => $rows, 'message' => $i->message, 'site' => Site::NAME, 'adminUrl' => Site::url('/admin/inquiries/'.$i->id)]);
    }
}
