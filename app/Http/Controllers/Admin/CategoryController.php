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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $archived = $request->query('view') === 'archived';
        $categories = ProductCategory::query()->where('status', $archived ? '=' : '!=', 'archived')->orderBy('sort_order')->get();
        $counts = Product::query()->selectRaw('category_id, count(*) as n')->groupBy('category_id')->pluck('n', 'category_id');

        return view('admin.categories.index', ['categories' => $categories, 'counts' => $counts, 'archived' => $archived]);
    }

    public function create(Request $request): View
    {
        return view('admin.categories.edit', ['category' => null, 'form' => new FormState([], $request->session()->get('errors'))]);
    }

    public function edit(Request $request, string $id): View
    {
        $category = Entities::find('categories', $id);

        return view('admin.categories.edit', ['category' => $category, 'form' => new FormState(Values::category($category), $request->session()->get('errors'))]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return $this->save($request, Entities::find('categories', $id));
    }

    private function save(Request $request, ?ProductCategory $record): RedirectResponse
    {
        $p = new FormParser($request);
        $name = $p->text('name', 'Name', 80, required: true);
        $row = ['name' => $name, 'slug' => $p->slug('slug', $name), 'description' => $p->text('description', 'Description', 400)];
        if (! $p->ok()) {
            return Saver::invalid($p);
        }

        return Saver::save($request, ProductCategory::class, 'categories', $record, $row);
    }
}
