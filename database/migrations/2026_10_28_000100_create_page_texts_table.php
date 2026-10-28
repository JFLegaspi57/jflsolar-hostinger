<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable page wording (Admin → Page text). Each row overrides one default in
 * App\Support\PageTextData; a missing row means the original wording is shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_texts', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_texts');
    }
};
