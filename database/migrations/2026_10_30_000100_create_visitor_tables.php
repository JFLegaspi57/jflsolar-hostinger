<?php

use App\Support\Visitors;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Visitor counts (admin → Visitors). The website also creates these itself if missing. */
    public function up(): void
    {
        Cache::forget('visitor-tables-v3');
        Visitors::ensureTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('visitor_days');
        Schema::dropIfExists('visitor_ids');
        Cache::forget('visitor-tables-v3');
    }
};
