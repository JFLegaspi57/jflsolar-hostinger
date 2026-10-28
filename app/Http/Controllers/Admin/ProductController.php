<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Entities;
use App\Admin\FormParser;
use App\Admin\FormState;
use App\Admin\Saver;
use App\Admin\Values;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Labels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $archived = $request->query('view') === 'archived';
        $q = mb_strtolower(trim(mb_substr((string) $request->query('q', ''), 0, 80)));
        $category = (string) $request->query('category', '');
        $all = Product::query()->where('status', $archived ? '=' : '!=', 'archived')->orderBy('sort_order')->orderBy('created_at')->get();
        $filtering = $q !== '' || $category !== '';
        $rows = $all->filter(fn ($p) => ($category === '' || $p->category_id === $category)
            && ($q === '' || str_contains(mb_strtolower(Str::ascii(implode(' ', array_filter([$p->name, $p->brand, $p->model, $p->sku])))), Str::ascii($q))))->values();

        return view('admin.products.index', [
            'products' => $rows, 'total' => $all->count(), 'archived' => $archived, 'q' => $q, 'category' => $category,
            'filtering' => $filtering, 'categories' => ProductCategory::query()->orderBy('sort_order')->get(['id', 'name']),
            'keep' => http_build_query(array_filter(['q' => $q, 'category' => $category])),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form($request, null);
    }

    public function edit(Request $request, string $id): View
    {
        $product = Entities::find('products', $id);
        $media = $product->media()->get();
        $product->images = $media->where('kind', 'image')->values();
        $product->datasheet = $media->firstWhere('kind', 'datasheet');

        return $this->form($request, $product);
    }

    private function form(Request $request, ?Product $product): View
    {
        return view('admin.products.edit', [
            'product' => $product,
            'form' => new FormState(Values::product($product), $request->session()->get('errors')),
            'categories' => ProductCategory::query()->where('status', '!=', 'archived')->orderBy('sort_order')->get(),
            'back' => Saver::safeBack($request->query('back')) ? $request->query('back') : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return $this->save($request, Entities::find('products', $id));
    }

    private function save(Request $request, ?Product $record): RedirectResponse
    {
        $p = new FormParser($request);
        $name = $p->text('name', 'Name', 140, required: true, min: 2);
        $pricingMode = $p->choice('pricing_mode', 'Pricing mode', array_keys(Labels::PRICING_MODE), required: true);
        $price = $p->number('price_php', 'Price', 100_000_000);
        if (in_array($pricingMode, ['fixed', 'starting'], true) && $price === null && ! isset($p->errors['price_php'])) {
            $p->errors['price_php'] = 'Enter a price for fixed or starting-price mode, or choose “Request price”.';
        }
        if ($pricingMode === 'quote') {
            $price = null;
        }
        $categoryId = (string) $request->input('category_id');
        if (! ProductCategory::query()->whereKey($categoryId)->exists()) {
            $p->errors['category_id'] = 'Choose a category.';
        }

        $row = [
            'name' => $name,
            'slug' => $p->slug('slug', $name),
            'sku' => $p->sku('sku'),
            'brand' => $p->text('brand', 'Brand', 60),
            'model' => $p->text('model', 'Model', 80),
            'category_id' => $categoryId,
            'summary' => $p->body('summary', 'Summary', 300),
            'description' => $p->body('description', 'Description', 6000),
            'specs' => $p->specs('specs'),
            'pricing_mode' => $pricingMode,
            'price_php' => $price,
            'price_basis' => $p->text('price_basis', 'Price basis', 200),
            'price_is_sample' => $p->bool('price_is_sample'),
            'availability' => $p->choice('availability', 'Availability', array_keys(Labels::AVAILABILITY), required: true),
            'warranty_terms' => $p->text('warranty_terms', 'Warranty terms', 2000),
            'admin_notes' => $p->text('admin_notes', 'Internal notes', 2000),
            'featured' => $p->bool('featured'),
            'is_sample' => $p->bool('is_sample'),
        ];
        if (! $p->ok()) {
            return Saver::invalid($p);
        }

        return Saver::save($request, Product::class, 'products', $record, $row);
    }
}
