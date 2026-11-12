<?php

use App\Support\Blog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Blog. On Hostinger the table is created on first use instead (Blog::ensureTables).
return new class extends Migration
{
    public function up(): void
    {
        Blog::forgetTables();
        Blog::ensureTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
        if (Schema::hasColumn('media_assets', 'blog_post_id')) {
            Schema::table('media_assets', fn (Blueprint $t) => $t->dropColumn('blog_post_id'));
        }
        Blog::forgetTables();
    }
};
