<?php

namespace App\Mail;

use App\Models\Member;
use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "Choose a new password" link for a member account. */
class MemberPasswordReset extends Mailable
{
    public function __construct(public Member $member, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your '.Site::NAME.' password');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.member-password-reset', with: ['name' => $this->member->name, 'url' => $this->url, 'site' => Site::NAME]);
    }
}
