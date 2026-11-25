<?php

namespace App\Mail;

use App\Models\Member;
use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "Confirm your new email address" link, sent to the new address. */
class MemberEmailChange extends Mailable
{
    public function __construct(public Member $member, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your new email for your '.Site::NAME.' account');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.member-email-change', with: ['name' => $this->member->name, 'url' => $this->url, 'site' => Site::NAME]);
    }
}
