@php
    use App\Support\Labels;
    $isNew = $package === null;
    $promo = $package?->promoState();
@endphp
<x-admin.layout :title="$isNew ? 'New package' : $package->name">
    <x-admin.page-title :title="$isNew ? 'New package' : $package->name" back="/admin/packages" back-label="All packages"
        :intro="$isNew ? '“Save as draft” keeps it hidden while you work; “Save and publish” puts it on the website straight away. You can add images after the first save.' : null" />

    @unless ($isNew)
        <x-admin.edit-header entity="packages" :record="$package" :name="$package->name" :public-href="'/packages/'.$package->slug" :preview-href="'/admin/preview/packages/'.$package->id">
            @if ($promo === 'ended')
                <p class="mt-3 rounded bg-danger-50 px-3 py-2 text-sm text-danger-700">The promotion ended on {{ $package->promoEnd() }}. {{ $package->hide_when_expired ? 'This package is hidden from the public site.' : 'The public page shows “Promotion ended — request current price”.' }} Update the dates or price, or archive it.</p>
            @endif
        </x-admin.edit-header>
    @endunless

    <x-admin.form :action="$isNew ? '/admin/packages' : '/admin/packages/'.$package->id" :form="$form" :status="$package?->status" :is-new="$isNew"
        :hidden="$isNew ? [] : ['lock_version' => $package->lock_version, 'back' => request('back')]">
        <x-admin.fieldset legend="Basics">
            <x-admin.text :form="$form" name="name" label="Package name" required maxlength="120" class="sm:col-span-2" />
            <x-admin.text :form="$form" name="slug" label="URL slug" maxlength="80" hint="The web address: /packages/your-slug. Leave blank to create it from the name. Changing it breaks old links." />
            <x-admin.text :form="$form" name="sku" label="SKU / code" maxlength="40" hint="Optional internal code for future ordering." />
            <x-admin.select :form="$form" name="package_type" label="Offer type" required :options="['installed_system' => 'Installed system (supply + installation)', 'equipment_bundle' => 'Equipment only (no installation)']" />
            <x-admin.select :form="$form" name="system_type" label="System type" :options="['' => 'Not specified'] + Labels::SYSTEM_TYPE" />
            <x-admin.textarea :form="$form" name="summary" label="Short summary" maxlength="300" rows="2" class="sm:col-span-2" hint="One or two sentences shown on package cards." />
            <x-admin.textarea :form="$form" name="description" label="Description" maxlength="6000" rows="5" class="sm:col-span-2" hint="Separate paragraphs with a blank line." />
        </x-admin.fieldset>

        <x-admin.fieldset legend="Capacity" description="Leave blank if it will be set after a site assessment.">
            <x-admin.text :form="$form" name="inverter_kw" label="Inverter capacity (kW)" inputmode="decimal" hint="How much power the inverter can deliver at once, e.g. 6." />
            <x-admin.text :form="$form" name="array_kwp" label="Solar array (kWp)" inputmode="decimal" hint="Total panel capacity: number of panels × panel watts ÷ 1000, e.g. 10 × 620 W = 6.2." />
            <x-admin.text :form="$form" name="battery_kwh" label="Battery (kWh)" inputmode="decimal" hint="How much energy the battery stores, e.g. 16.5. Leave blank if no battery." />
            <x-admin.textarea :form="$form" name="components" label="Components" rows="4" mono class="sm:col-span-2"
                :hint="new \Illuminate\Support\HtmlString('One per line: <code>Component | details | quantity</code> — e.g. <code>Hybrid inverter | GoodWe GW6000-ES-C10, 6 kW | 1 unit</code>')" />
            <x-admin.textarea :form="$form" name="inclusions" label="Included" rows="5" hint="One item per line." />
            <x-admin.textarea :form="$form" name="exclusions" label="Not included" rows="5" hint="One item per line." />
        </x-admin.fieldset>

        <x-admin.fieldset legend="Price" description="Prices are shown in Philippine pesos. Choose “Request a quotation” when there is no published price.">
            <x-admin.select :form="$form" name="pricing_mode" label="Pricing mode" required :options="['quote' => 'Request a quotation (no price shown)', 'fixed' => 'Fixed price', 'starting' => 'Starting price (“From ₱…”)']" />
            <x-admin.text :form="$form" name="price_php" label="Price (₱)" inputmode="decimal" hint="Numbers only, e.g. 205000." class="needs-price" />
            <p class="quote-note self-center text-sm text-steel-700">No price will be shown. Customers see “Price to be confirmed” and are asked to request a quotation.</p>
            <x-admin.text :form="$form" name="price_basis" label="Price basis" maxlength="200" hint="e.g. “Pickup price, equipment only” or “Installed price, Metro Manila”." class="needs-price sm:col-span-2" />
            <div class="needs-price"><x-admin.checkbox :form="$form" name="price_is_sample" label="This is a sample price" hint="Shows “Sample price — not a confirmed offer”. Untick only for an approved price." /></div>
        </x-admin.fieldset>

        <x-admin.fieldset legend="Promotion" description="Optional. Dates are inclusive, Philippine time. After the end date the promotional price is withdrawn automatically.">
            <x-admin.text :form="$form" name="promo_label" label="Promotion label" maxlength="80" placeholder="e.g. October 2026 promotion" class="sm:col-span-2" />
            <x-admin.text :form="$form" name="promo_start" label="Start date" type="date" />
            <x-admin.text :form="$form" name="promo_end" label="End date" type="date" />
            <x-admin.checkbox :form="$form" name="hide_when_expired" label="Hide this package after the promotion ends" hint="Otherwise it stays listed with “Promotion ended — request current price”." />
        </x-admin.fieldset>

        <x-admin.fieldset legend="Installation, delivery and warranty">
            <x-admin.textarea :form="$form" name="installation_terms" label="Installation terms" maxlength="1000" rows="3" class="sm:col-span-2" />
            <x-admin.select :form="$form" name="fulfillment" label="Pickup / delivery" required :options="['to_be_confirmed' => 'To be confirmed at quotation', 'installed' => 'Installed on site', 'pickup' => 'Pickup at warehouse', 'delivery' => 'Delivery', 'pickup_or_delivery' => 'Pickup or delivery']" />
            <x-admin.text :form="$form" name="fulfillment_notes" label="Pickup / delivery notes" maxlength="600" />
            <x-admin.textarea :form="$form" name="warranty_terms" label="Warranty terms" maxlength="2000" rows="3" class="sm:col-span-2" hint="Leave blank to show the general warranty text from Settings. Do not enter terms that are not confirmed." />
        </x-admin.fieldset>

        <x-admin.fieldset legend="Display and internal">
            <x-admin.checkbox :form="$form" name="featured" label="Featured on the home page" />
            <x-admin.checkbox :form="$form" name="is_sample" label="Sample / demonstration content" hint="Sample items are hidden on the live site. Untick once the content is real and approved." />
            <x-admin.textarea :form="$form" name="admin_notes" label="Internal notes (never shown publicly)" maxlength="2000" rows="3" class="sm:col-span-2" />
        </x-admin.fieldset>
    </x-admin.form>

    @unless ($isNew)
        <div class="mb-28 space-y-8">
            <details class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                <summary class="inline-flex min-h-11 cursor-pointer items-center gap-2 font-semibold text-steel-900"><span aria-hidden="true">▸</span> Show saved card preview</summary>
                @php $card = clone $package; $card->promo_state = $promo; @endphp
                <div class="mt-3 max-w-sm"><x-package-card :pkg="$card" show-image admin /></div>
            </details>
            <x-admin.image-manager owner-type="package" :owner="$package" :images="$package->images" />
        </div>
    @endunless
</x-admin.layout>
