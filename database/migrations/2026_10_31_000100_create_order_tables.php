<?php

use App\Support\Shop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Online orders (cart + PayMongo). The website also creates these itself if missing. */
    public function up(): void
    {
        Cache::forget('shop-tables-v1');
        Cache::forget('shop-tables-v2');
        Shop::ensureTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Cache::forget('shop-tables-v1');
        Cache::forget('shop-tables-v2');
    }
};
