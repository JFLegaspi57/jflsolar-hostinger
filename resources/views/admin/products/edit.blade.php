@php
    use App\Support\Labels;
    $isNew = $product === null;
    $backHref = $back ?? '/admin/products';
@endphp
<x-admin.layout :title="$isNew ? 'New product' : $product->name">
    <x-admin.page-title :title="$isNew ? 'New product' : $product->name" :back="$backHref" :back-label="$back ? 'Back to your product search' : 'All products'"
        :intro="$isNew ? '“Save as draft” keeps it hidden while you work; “Save and publish” puts it on the website straight away. You can add images and a datasheet after the first save.' : null" />

    @unless ($isNew)
        <x-admin.edit-header entity="products" :record="$product" :name="$product->name" :public-href="'/products/'.$product->slug" :preview-href="'/admin/preview/products/'.$product->id" />
    @endunless

    <x-admin.form :action="$isNew ? '/admin/products' : '/admin/products/'.$product->id" :form="$form" :status="$product?->status" :is-new="$isNew"
        :hidden="$isNew ? [] : ['lock_version' => $product->lock_version, 'back' => $back]">
        <x-admin.fieldset legend="Basics">
            <x-admin.text :form="$form" name="name" label="Product name" required maxlength="140" class="sm:col-span-2" />
            <x-admin.select :form="$form" name="category_id" label="Category" required :options="['' => 'Choose a category…'] + $categories->pluck('name', 'id')->all()" />
            <x-admin.text :form="$form" name="slug" label="URL slug" maxlength="80" hint="/products/your-slug — leave blank to create from the name." />
            <x-admin.text :form="$form" name="brand" label="Brand" maxlength="60" />
            <x-admin.text :form="$form" name="model" label="Model" maxlength="80" />
            <x-admin.text :form="$form" name="sku" label="SKU / code" maxlength="40" hint="Optional internal code for future ordering." />
            <x-admin.textarea :form="$form" name="summary" label="Short summary" maxlength="300" rows="2" class="sm:col-span-2" />
            <x-admin.textarea :form="$form" name="description" label="Description" maxlength="6000" rows="4" class="sm:col-span-2" />
            <x-admin.textarea :form="$form" name="specs" label="Specifications" rows="6" mono class="sm:col-span-2"
                :hint="new \Illuminate\Support\HtmlString('One per line: <code>Label: value</code> — e.g. <code>Rated power: 620 W</code>. Only enter confirmed specifications.')" />
        </x-admin.fieldset>
        <x-admin.fieldset legend="Price and availability">
            <x-admin.select :form="$form" name="pricing_mode" label="Pricing mode" required :options="['quote' => 'Request price (no price shown)', 'fixed' => 'Fixed price', 'starting' => 'Starting price (“From ₱…”)']" />
            <x-admin.text :form="$form" name="price_php" label="Price (₱)" inputmode="decimal" hint="Numbers only, e.g. 18500." class="needs-price" />
            <p class="quote-note self-center text-sm text-steel-700">No price will be shown. Customers see “Request price”.</p>
            <x-admin.text :form="$form" name="price_basis" label="Price basis" maxlength="200" class="needs-price sm:col-span-2" hint="e.g. “Per piece, pickup price”." />
            <div class="needs-price"><x-admin.checkbox :form="$form" name="price_is_sample" label="This is a sample price" /></div>
            <x-admin.select :form="$form" name="availability" label="Availability" required hint="Shown as a general status — not a live stock count."
                :options="['on_request' => 'Ask about availability', 'available' => 'Available to order', 'preorder' => 'Available on pre-order', 'unavailable' => 'Currently unavailable']" />
            <x-admin.textarea :form="$form" name="warranty_terms" label="Warranty terms" maxlength="2000" rows="3" class="sm:col-span-2" hint="Leave blank to show the general warranty text. Only enter confirmed terms." />
        </x-admin.fieldset>
        <x-admin.fieldset legend="Display and internal">
            <x-admin.checkbox :form="$form" name="featured" label="Featured product" />
            <x-admin.checkbox :form="$form" name="is_sample" label="Sample / demonstration content" hint="Hidden on the live site." />
            <x-admin.textarea :form="$form" name="admin_notes" label="Internal notes (never shown publicly)" maxlength="2000" rows="3" class="sm:col-span-2" />
        </x-admin.fieldset>
    </x-admin.form>

    @unless ($isNew)
        <div class="mb-28 space-y-8">
            <details class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                <summary class="inline-flex min-h-11 cursor-pointer items-center gap-2 font-semibold text-steel-900"><span aria-hidden="true">▸</span> Show saved card preview</summary>
                @php $card = clone $product; $card->setRelation('category', $categories->firstWhere('id', $product->category_id)); @endphp
                <div class="mt-3 max-w-xs"><x-product-card :product="$card" admin /></div>
            </details>
            <x-admin.image-manager owner-type="product" :owner="$product" :images="$product->images" title="Product images" cover-label="main image" />

            @php $sheetError = $errors->getBag('datasheet')->first('file'); @endphp
            <section id="datasheet" aria-labelledby="ds-title" class="scroll-mt-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5 sm:p-6">
                <h2 id="ds-title" class="text-lg font-bold text-steel-950">Datasheet (PDF)</h2>
                @if ($product->datasheet)
                    <p class="mt-1 text-sm text-steel-700">Current: <a href="{{ $product->datasheet->url(null, true) }}" target="_blank" rel="noopener" class="font-semibold underline">{{ $product->datasheet->file_name ?? 'datasheet.pdf' }}</a>{{ $product->datasheet->bytes ? ' ('.round($product->datasheet->bytes / 1024).' KB)' : '' }}</p>
                @else
                    <p class="mt-1 text-sm text-steel-600">No datasheet uploaded. Use the manufacturer’s official datasheet only.</p>
                @endif
                @if (session('datasheet_status'))<p role="status" class="mt-4 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 px-3 py-2 text-sm font-semibold text-ok-700">{{ session('datasheet_status') }}</p>@endif
                <form method="post" action="/admin/products/{{ $product->id }}/datasheet" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label for="ds-file" class="mb-1 block font-semibold">{{ $product->datasheet ? 'Replace with' : 'Upload' }} PDF <span class="text-sm font-normal text-steel-600">(max 4 MB)</span></label>
                        <input id="ds-file" name="file" type="file" accept="application/pdf" required @if ($sheetError) aria-invalid="true" aria-describedby="ds-file-error" @endif class="block text-sm file:mr-3 file:min-h-11 file:rounded file:border-0 file:bg-steel-900 file:px-4 file:font-semibold file:text-white">
                        @if ($sheetError)<p id="ds-file-error" role="alert" class="mt-1 text-sm font-semibold text-danger-700">{{ $sheetError }}</p>@endif
                    </div>
                    <button type="submit" data-pending-label="Uploading…" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-steel-900 px-5 font-semibold text-white disabled:opacity-60">Upload datasheet</button>
                </form>
                @if ($product->datasheet)
                    <div class="mt-3">
                        <x-admin.confirm :action="'/admin/products/'.$product->id.'/datasheet/delete'" title="Remove the datasheet?" confirm-label="Remove datasheet" label="Remove datasheet">
                            <p>The PDF is deleted and the download link disappears from the product page.</p>
                        </x-admin.confirm>
                    </div>
                @endif
            </section>

            @php $manualError = $errors->getBag('manual')->first('file'); @endphp
            <section id="manual" aria-labelledby="um-title" class="scroll-mt-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5 sm:p-6">
                <h2 id="um-title" class="text-lg font-bold text-steel-950">User manual (PDF)</h2>
                @if ($product->manual)
                    <p class="mt-1 text-sm text-steel-700">Current: <a href="{{ $product->manual->url(null, true) }}" target="_blank" rel="noopener" class="font-semibold underline">{{ $product->manual->file_name ?? 'manual.pdf' }}</a>{{ $product->manual->bytes ? ' ('.round($product->manual->bytes / 1024).' KB)' : '' }}</p>
                @else
                    <p class="mt-1 text-sm text-steel-600">No user manual uploaded. Use the manufacturer’s official manual only. It appears on the product page right below the datasheet.</p>
                @endif
                @if (session('manual_status'))<p role="status" class="mt-4 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 px-3 py-2 text-sm font-semibold text-ok-700">{{ session('manual_status') }}</p>@endif
                <form method="post" action="/admin/products/{{ $product->id }}/manual" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label for="um-file" class="mb-1 block font-semibold">{{ $product->manual ? 'Replace with' : 'Upload' }} PDF <span class="text-sm font-normal text-steel-600">(max 4 MB)</span></label>
                        <input id="um-file" name="file" type="file" accept="application/pdf" required @if ($manualError) aria-invalid="true" aria-describedby="um-file-error" @endif class="block text-sm file:mr-3 file:min-h-11 file:rounded file:border-0 file:bg-steel-900 file:px-4 file:font-semibold file:text-white">
                        @if ($manualError)<p id="um-file-error" role="alert" class="mt-1 text-sm font-semibold text-danger-700">{{ $manualError }}</p>@endif
                    </div>
                    <button type="submit" data-pending-label="Uploading…" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-steel-900 px-5 font-semibold text-white disabled:opacity-60">Upload user manual</button>
                </form>
                @if ($product->manual)
                    <div class="mt-3">
                        <x-admin.confirm :action="'/admin/products/'.$product->id.'/manual/delete'" title="Remove the user manual?" confirm-label="Remove user manual" label="Remove user manual">
                            <p>The PDF is deleted and the download link disappears from the product page.</p>
                        </x-admin.confirm>
                    </div>
                @endif
            </section>
        </div>
    @endunless
</x-admin.layout>
