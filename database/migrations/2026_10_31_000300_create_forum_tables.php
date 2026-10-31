<?php

use App\Support\Forum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Forum. On Hostinger the tables are created on first use instead (Forum::ensureTables).
return new class extends Migration
{
    public function up(): void
    {
        Forum::forgetTables();
        Forum::ensureTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
        Schema::dropIfExists('forum_topics');
        Forum::forgetTables();
    }
};
