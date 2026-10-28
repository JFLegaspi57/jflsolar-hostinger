<?php

namespace App\Admin;

use App\Models\Package;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;

/** The four list-managed record types and how the shared controls treat them. */
final class Entities
{
    public const MAP = [
        'packages' => ['model' => Package::class, 'singular' => 'package', 'media' => 'package_id', 'featured' => true, 'title' => 'name'],
        'products' => ['model' => Product::class, 'singular' => 'product', 'media' => 'product_id', 'featured' => true, 'title' => 'name'],
        'projects' => ['model' => Project::class, 'singular' => 'project', 'media' => 'project_id', 'featured' => true, 'title' => 'title'],
        'categories' => ['model' => ProductCategory::class, 'singular' => 'category', 'media' => null, 'featured' => false, 'title' => 'name'],
    ];

    public static function get(string $entity): array
    {
        return self::MAP[$entity] ?? abort(404);
    }

    public static function find(string $entity, string $id): \Illuminate\Database\Eloquent\Model
    {
        abort_unless(preg_match('/^[0-9a-f-]{36}$/', $id), 404);

        return self::get($entity)['model']::query()->find($id) ?? abort(404);
    }
}
