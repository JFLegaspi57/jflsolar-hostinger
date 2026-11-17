<?php

namespace App\Mail;

use App\Models\Member;
use App\Support\Newsletter;
use App\Support\PublicContent;
use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\DB;

/** Welcome email to a new member: thank you from the team, and a quick tour of the website. */
class MemberWelcome extends Mailable
{
    public function __construct(public Member $member) {}

    public function envelope(): Envelope
    {
        $s = PublicContent::settings();

        return new Envelope(
            subject: 'Thank you from the whole JFL Solar team + a quick tour of our website',
            replyTo: filled($s->public_email) ? [new Address($s->public_email, Site::NAME)] : [],
        );
    }

    public function content(): Content
    {
        $s = PublicContent::settings();
        $token = Newsletter::hasTables() ? DB::table('newsletter_subscribers')->where('email', mb_strtolower($this->member->email))->whereNull('unsubscribed_at')->value('token') : null;

        return new Content(html: 'mail.member-welcome-html', text: 'mail.member-welcome', with: [
            'name' => trim(explode(' ', (string) $this->member->name)[0] ?? ''),
            'phone' => '0969 471 2098',   // Sales
            'u' => fn (string $path = '') => Site::url($path),
            'unsubscribeUrl' => $token ? Newsletter::unsubscribeUrl($token) : null,
        ]);
    }
}
