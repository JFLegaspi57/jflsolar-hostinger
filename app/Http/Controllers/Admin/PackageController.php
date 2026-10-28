<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Entities;
use App\Admin\FormParser;
use App\Admin\FormState;
use App\Admin\Saver;
use App\Admin\Values;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Support\Labels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        $archived = $request->query('view') === 'archived';
        $packages = Package::query()->where('status', $archived ? '=' : '!=', 'archived')->orderBy('sort_order')->orderBy('created_at')->get();

        return view('admin.packages.index', ['packages' => $packages, 'archived' => $archived]);
    }

    public function create(Request $request): View
    {
        return view('admin.packages.edit', ['package' => null, 'form' => new FormState(Values::package(null), $request->session()->get('errors'))]);
    }

    public function edit(Request $request, string $id): View
    {
        $package = Entities::find('packages', $id);
        $package->images = $package->media()->where('kind', 'image')->get();

        return view('admin.packages.edit', ['package' => $package, 'form' => new FormState(Values::package($package), $request->session()->get('errors'))]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return $this->save($request, Entities::find('packages', $id));
    }

    private function save(Request $request, ?Package $record): RedirectResponse
    {
        $p = new FormParser($request);
        $name = $p->text('name', 'Name', 120, required: true, min: 2);
        $pricingMode = $p->choice('pricing_mode', 'Pricing mode', array_keys(Labels::PRICING_MODE), required: true);
        $price = $p->number('price_php', 'Price', 100_000_000);
        if (in_array($pricingMode, ['fixed', 'starting'], true) && $price === null && ! isset($p->errors['price_php'])) {
            $p->errors['price_php'] = 'Enter a price for fixed or starting-price mode, or choose “Request a quotation”.';
        }
        if ($pricingMode === 'quote') {
            $price = null;
        }
        $promoStart = $p->date('promo_start', 'Promotion start');
        $promoEnd = $p->date('promo_end', 'Promotion end');
        if ($promoStart && $promoEnd && $promoEnd < $promoStart) {
            $p->errors['promo_end'] = 'Promotion end must be on or after the start date.';
        }

        $row = [
            'name' => $name,
            'slug' => $p->slug('slug', $name),
            'sku' => $p->sku('sku'),
            'summary' => $p->body('summary', 'Summary', 300),
            'description' => $p->body('description', 'Description', 6000),
            'package_type' => $p->choice('package_type', 'Package type', array_keys(Labels::PACKAGE_TYPE), required: true),
            'system_type' => $p->choice('system_type', 'System type', array_keys(Labels::SYSTEM_TYPE)),
            'inverter_kw' => $p->number('inverter_kw', 'Inverter capacity', 100000, positive: true),
            'array_kwp' => $p->number('array_kwp', 'Array capacity', 100000, positive: true),
            'battery_kwh' => $p->number('battery_kwh', 'Battery capacity', 100000, positive: true),
            'components' => $p->components('components'),
            'inclusions' => $p->lines('inclusions', 'Inclusions', 40, 200),
            'exclusions' => $p->lines('exclusions', 'Exclusions', 40, 200),
            'pricing_mode' => $pricingMode,
            'price_php' => $price,
            'price_basis' => $p->text('price_basis', 'Price basis', 200),
            'price_is_sample' => $p->bool('price_is_sample'),
            'installation_terms' => $p->text('installation_terms', 'Installation terms', 1000),
            'fulfillment' => $p->choice('fulfillment', 'Pickup/delivery', array_keys(Labels::FULFILLMENT), required: true),
            'fulfillment_notes' => $p->text('fulfillment_notes', 'Pickup/delivery notes', 600),
            'promo_label' => $p->text('promo_label', 'Promotion label', 80),
            'promo_start' => $promoStart,
            'promo_end' => $promoEnd,
            'hide_when_expired' => $p->bool('hide_when_expired'),
            'warranty_terms' => $p->text('warranty_terms', 'Warranty terms', 2000),
            'admin_notes' => $p->text('admin_notes', 'Internal notes', 2000),
            'featured' => $p->bool('featured'),
            'is_sample' => $p->bool('is_sample'),
        ];
        if (! $p->ok()) {
            return Saver::invalid($p);
        }

        return Saver::save($request, Package::class, 'packages', $record, $row);
    }
}
