<?php

namespace App\Support;

use App\Models\SiteSetting;

final class Contact
{
    /**
     * Enabled public contact channels, in priority order. Disabled/unverified
     * channels are omitted.
     *
     * @return list<array{key: string, label: string, detail: string, href: string, external: bool}>
     */
    public static function channels(SiteSetting $s): array
    {
        $list = [['key' => 'phone', 'label' => 'Call', 'detail' => $s->phone_display, 'href' => Site::telHref($s->phone_e164), 'external' => false]];
        if ($s->whatsapp_enabled && $s->whatsapp_e164) {
            $list[] = ['key' => 'whatsapp', 'label' => 'WhatsApp', 'detail' => $s->messaging_display ?? $s->whatsapp_e164, 'href' => Site::whatsappHref($s->whatsapp_e164), 'external' => true];
        }
        if ($s->viber_enabled && $s->viber_e164) {
            $list[] = ['key' => 'viber', 'label' => 'Viber', 'detail' => $s->messaging_display ?? $s->viber_e164, 'href' => Site::viberHref($s->viber_e164), 'external' => false];
        }
        if ($s->messenger_enabled && $s->messenger_url) {
            $list[] = ['key' => 'messenger', 'label' => 'Messenger', 'detail' => 'Message us on Facebook', 'href' => $s->messenger_url, 'external' => true];
        }
        if ($s->facebook_enabled && $s->facebook_url) {
            $list[] = ['key' => 'facebook', 'label' => 'Facebook', 'detail' => 'JFL Solar on Facebook', 'href' => $s->facebook_url, 'external' => true];
        }
        if ($s->public_email) {
            $list[] = ['key' => 'email', 'label' => 'Email', 'detail' => $s->public_email, 'href' => 'mailto:'.$s->public_email, 'external' => false];
        }

        return $list;
    }
}
