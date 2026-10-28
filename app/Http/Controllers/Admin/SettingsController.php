<?php

namespace App\Http\Controllers\Admin;

use App\Admin\FormParser;
use App\Admin\FormState;
use App\Admin\Saver;
use App\Admin\Values;
use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $settings = SiteSetting::query()->find(1) ?? abort(500, 'site_settings row missing');

        return view('admin.settings', ['settings' => $settings, 'form' => new FormState(Values::settings($settings), $request->session()->get('errors'))]);
    }

    public function update(Request $request): RedirectResponse
    {
        $p = new FormParser($request);
        $e164 = function (string $name, string $label, bool $required = false) use ($p) {
            $v = $p->text($name, $label, 16, required: $required);
            if ($v && ! preg_match('/^\+[1-9][0-9]{7,14}$/', $v)) {
                $p->errors[$name] = "{$label}: use international format, e.g. +639171234567.";
            }

            return $v;
        };
        $url = function (string $name, string $label, string $re, string $example) use ($p) {
            $v = $p->text($name, $label, 300);
            if ($v && ! preg_match($re, $v)) {
                $p->errors[$name] = "{$label}: enter a full link such as {$example}.";
            }

            return $v;
        };

        $row = [
            'phone_display' => $p->text('phone_display', 'Phone (as displayed)', 40, required: true),
            'phone_e164' => $e164('phone_e164', 'Phone (international)', true),
            'messaging_display' => $p->text('messaging_display', 'Messaging number (as displayed)', 40),
            'whatsapp_e164' => $e164('whatsapp_e164', 'WhatsApp number'),
            'whatsapp_enabled' => $p->bool('whatsapp_enabled'),
            'viber_e164' => $e164('viber_e164', 'Viber number'),
            'viber_enabled' => $p->bool('viber_enabled'),
            'facebook_url' => $url('facebook_url', 'Facebook page', '#^https://(www\.|m\.)?facebook\.com/[A-Za-z0-9._/-]+$#', 'https://www.facebook.com/yourpage'),
            'facebook_enabled' => $p->bool('facebook_enabled'),
            'messenger_url' => $url('messenger_url', 'Messenger link', '#^https://(m\.me|www\.messenger\.com)/[A-Za-z0-9._/-]+$#', 'https://m.me/yourpage'),
            'messenger_enabled' => $p->bool('messenger_enabled'),
            'public_email' => $p->text('public_email', 'Public email', 254),
            'office_address' => $p->text('office_address', 'Office address', 300, required: true),
            'warehouse_address' => $p->text('warehouse_address', 'Warehouse address', 300),
            'service_area' => $p->text('service_area', 'Service area', 300, required: true),
            'service_area_note' => $p->text('service_area_note', 'Service area note', 1000),
            'business_hours' => $p->text('business_hours', 'Business hours', 300),
            'general_warranty' => $p->text('general_warranty', 'General warranty information', 4000),
        ];
        if ($row['public_email'] && ! filter_var($row['public_email'], FILTER_VALIDATE_EMAIL)) {
            $p->errors['public_email'] = 'Enter a valid email address.';
        }
        foreach ([['whatsapp_enabled', 'whatsapp_e164', 'WhatsApp number'], ['viber_enabled', 'viber_e164', 'Viber number'], ['facebook_enabled', 'facebook_url', 'Facebook link'], ['messenger_enabled', 'messenger_url', 'Messenger link']] as [$flag, $field, $label]) {
            if ($row[$flag] && ! $row[$field] && ! isset($p->errors[$field])) {
                $p->errors[$field] = "Enter the {$label} before turning this button on.";
            }
        }
        if (! $p->ok()) {
            return Saver::invalid($p);
        }

        $version = (int) $request->input('lock_version', -1);
        $updated = SiteSetting::query()->whereKey(1)->where('lock_version', $version)->update($row + ['lock_version' => $version + 1, 'updated_at' => now()]);
        if (! $updated) {
            return Saver::failed('Settings were changed by someone else after you opened this page. Reload and try again.');
        }
        PublicContent::flush();

        return redirect('/admin/settings')->with('status', 'Settings saved. The public site is updated.');
    }
}
