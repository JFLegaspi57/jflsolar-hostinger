<?php

namespace App\Mail;

use App\Models\Inquiry;
use App\Support\PublicContent;
use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Automatic reply to a customer whose bill points to a system below 6 kW: DIY options. */
class SmallSystemReply extends Mailable
{
    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        $s = PublicContent::settings();

        return new Envelope(
            subject: 'Thank you for your inquiry — options for a smaller solar PV system',
            replyTo: filled($s->public_email) ? [new Address($s->public_email, Site::NAME)] : [],
        );
    }

    public function content(): Content
    {
        $s = PublicContent::settings();

        return new Content(text: 'mail.small-system-reply', with: [
            'name' => $this->inquiry->name,
            'materialsUrl' => Site::url('/packages?type=materials'),
            'buildUrl' => Site::url('/packages/build-your-own'),
            'calculatorUrl' => Site::url('/pv-calculator'),
            'productsUrl' => Site::url('/products'),
            'phone' => $s->phone_display,
            'site' => Site::NAME,
            'siteUrl' => Site::url(),
        ]);
    }
}
