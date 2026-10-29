<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** YouTube / Facebook video links for a project, one per line. */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('video_urls')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('video_urls');
        });
    }
};
