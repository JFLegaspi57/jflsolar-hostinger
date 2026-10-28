<x-admin.layout title="Contact & settings">
    <x-admin.page-title title="Contact and settings" intro="Public contact details shown in the header, footer and Contact page." />
    <x-admin.form action="/admin/settings" :form="$form" :publishable="false" save-label="Save settings" :hidden="['lock_version' => $settings->lock_version]">
        <x-admin.fieldset legend="Phone and messaging" description="These are shown publicly.">
            <x-admin.text :form="$form" name="phone_display" label="Phone (as displayed)" required maxlength="40" placeholder="0969 471 2098" />
            <x-admin.text :form="$form" name="phone_e164" label="Phone (international format, for click-to-call)" required maxlength="16" placeholder="+639694712098" />
            <x-admin.text :form="$form" name="messaging_display" label="WhatsApp / Viber number (as displayed)" maxlength="40" />
            <div class="hidden sm:block"></div>
            <x-admin.text :form="$form" name="whatsapp_e164" label="WhatsApp number (international)" maxlength="16" />
            <x-admin.checkbox :form="$form" name="whatsapp_enabled" label="Show WhatsApp button" hint="Only turn on after confirming the number uses WhatsApp." />
            <x-admin.text :form="$form" name="viber_e164" label="Viber number (international)" maxlength="16" />
            <x-admin.checkbox :form="$form" name="viber_enabled" label="Show Viber button" hint="Only turn on after confirming the number uses Viber." />
        </x-admin.fieldset>
        <x-admin.fieldset legend="Facebook and email">
            <x-admin.text :form="$form" name="facebook_url" label="Facebook page link" type="url" maxlength="300" placeholder="https://www.facebook.com/…" />
            <x-admin.checkbox :form="$form" name="facebook_enabled" label="Show Facebook link" hint="Turn on only after checking the link opens your public page while logged out." />
            <x-admin.text :form="$form" name="messenger_url" label="Messenger link" type="url" maxlength="300" placeholder="https://m.me/…" />
            <x-admin.checkbox :form="$form" name="messenger_enabled" label="Show Messenger button" />
            <x-admin.text :form="$form" name="public_email" label="Public email address" type="email" maxlength="254" hint="Shown on the website. Optional — leave blank to hide." class="sm:col-span-2" />
        </x-admin.fieldset>
        <x-admin.fieldset legend="Locations">
            <x-admin.textarea :form="$form" name="office_address" label="Office address" required maxlength="300" rows="2" />
            <x-admin.textarea :form="$form" name="warehouse_address" label="Warehouse address" maxlength="300" rows="2" />
            <x-admin.text :form="$form" name="business_hours" label="Business hours" maxlength="300" hint="Optional. Leave blank until confirmed." class="sm:col-span-2" />
        </x-admin.fieldset>
        <x-admin.fieldset legend="Service area and warranty">
            <x-admin.text :form="$form" name="service_area" label="Service area" required maxlength="300" class="sm:col-span-2" />
            <x-admin.textarea :form="$form" name="service_area_note" label="Service area note" maxlength="1000" rows="2" class="sm:col-span-2" />
            <x-admin.textarea :form="$form" name="general_warranty" label="General warranty information" maxlength="4000" rows="4" class="sm:col-span-2" hint="Shown wherever a package or product has no specific warranty terms. Do not state periods or guarantees that are not confirmed." />
        </x-admin.fieldset>
    </x-admin.form>
</x-admin.layout>
