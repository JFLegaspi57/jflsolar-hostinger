<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_warranty_lines_show_as_labelled_paragraphs(): void
    {
        $this->seed();
        SiteSetting::query()->first()->update(['general_warranty' => "Products: Manufacturer warranty.\nInstallation: 1-year workmanship warranty."]);
        $product = Product::query()->where('status', 'published')->first();
        $product->update(['warranty_terms' => null]);
        PublicContent::flush();

        $this->get('/products/'.$product->slug)->assertOk()
            ->assertSee('<strong class="font-semibold text-steel-950">Products:</strong> Manufacturer warranty.', false)
            ->assertSee('<strong class="font-semibold text-steel-950">Installation:</strong> 1-year workmanship warranty.', false);
    }
}
