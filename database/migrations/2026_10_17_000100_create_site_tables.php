<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website content. Mirrors the original Supabase schema (same ids, so content
 * can be copied one-to-one). Value rules (lengths, formats, price/pricing-mode
 * consistency) are enforced by the admin validators; rules MySQL cannot express
 * (one cover image per item) are enforced in transactions by the app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary(); // always 1
            $table->string('phone_display', 40);
            $table->string('phone_e164', 16);
            $table->string('messaging_display', 40)->nullable();
            $table->string('whatsapp_e164', 16)->nullable();
            $table->boolean('whatsapp_enabled')->default(false);
            $table->string('viber_e164', 16)->nullable();
            $table->boolean('viber_enabled')->default(false);
            $table->string('facebook_url', 300)->nullable();
            $table->boolean('facebook_enabled')->default(false);
            $table->string('messenger_url', 300)->nullable();
            $table->boolean('messenger_enabled')->default(false);
            $table->string('public_email', 254)->nullable();
            $table->string('office_address', 300);
            $table->string('warehouse_address', 300)->nullable();
            $table->string('service_area', 300);
            $table->text('service_area_note')->nullable();
            $table->string('business_hours', 300)->nullable();
            $table->text('general_warranty')->nullable();
            $table->unsignedInteger('lock_version')->default(0); // optimistic concurrency: bumped on every save
            $table->timestamps();
        });

        Schema::create('content_blocks', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->string('section', 20); // about | why | home | services | legal
            $table->string('title', 160);
            $table->string('subtitle', 200)->nullable();
            $table->text('body');
            $table->string('claim_status', 20)->default('needs_confirmation'); // confirmed | needs_confirmation
            $table->text('review_note')->nullable();
            $table->boolean('published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('lock_version')->default(0); // optimistic concurrency: bumped on every save
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 80)->unique();
            $table->string('name', 80);
            $table->string('description', 400)->nullable();
            $table->string('status', 10)->default('published');
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('lock_version')->default(0); // optimistic concurrency: bumped on every save
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 80)->unique();
            $table->string('sku', 40)->nullable()->unique();
            $table->string('name', 120);
            $table->string('summary', 300)->default('');
            $table->text('description');
            $table->string('package_type', 20); // installed_system | equipment_bundle
            $table->string('system_type', 10)->nullable(); // on_grid | hybrid | off_grid
            $table->decimal('inverter_kw', 8, 2)->nullable();
            $table->decimal('array_kwp', 8, 2)->nullable();
            $table->decimal('battery_kwh', 8, 2)->nullable();
            $table->json('components'); // [{label, detail, quantity}]
            $table->json('inclusions'); // [string]
            $table->json('exclusions'); // [string]
            $table->string('pricing_mode', 10)->default('quote'); // fixed | starting | quote
            $table->decimal('price_php', 12, 2)->nullable();
            $table->string('price_basis', 200)->nullable();
            $table->boolean('price_is_sample')->default(false);
            $table->text('installation_terms')->nullable();
            $table->string('fulfillment', 20)->default('to_be_confirmed');
            $table->string('fulfillment_notes', 600)->nullable();
            $table->string('promo_label', 80)->nullable();
            $table->date('promo_start')->nullable();
            $table->date('promo_end')->nullable();
            $table->boolean('hide_when_expired')->default(false);
            $table->text('warranty_terms')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('status', 10)->default('draft');
            $table->boolean('featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_sample')->default(false);
            $table->unsignedInteger('lock_version')->default(0); // optimistic concurrency: bumped on every save
            $table->timestamps();
            $table->index(['status', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 80)->unique();
            $table->string('sku', 40)->nullable()->unique();
            $table->string('name', 140);
            $table->string('brand', 60)->nullable();
            $table->string('model', 80)->nullable();
            $table->foreignUuid('category_id')->constrained('product_categories')->restrictOnDelete();
            $table->string('summary', 300)->default('');
            $table->text('description');
            $table->json('specs'); // [{label, value}]
            $table->string('pricing_mode', 10)->default('quote');
            $table->decimal('price_php', 12, 2)->nullable();
            $table->string('price_basis', 200)->nullable();
            $table->boolean('price_is_sample')->default(false);
            $table->string('availability', 20)->default('on_request');
            $table->text('warranty_terms')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('status', 10)->default('draft');
            $table->boolean('featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_sample')->default(false);
            $table->unsignedInteger('lock_version')->default(0); // optimistic concurrency: bumped on every save
            $table->timestamps();
            $table->index(['status', 'category_id', 'sort_order']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 80)->unique();
            $table->string('title', 140);
            $table->string('location', 120)->nullable();
            $table->string('project_type', 20); // residential | commercial | industrial
            $table->decimal('system_size_kwp', 8, 2)->nullable();
            $table->boolean('system_size_confirmed')->default(false);
            $table->string('scope', 600)->nullable();
            $table->string('summary', 300)->default('');
            $table->text('description');
            $table->text('admin_notes')->nullable();
            $table->string('status', 10)->default('draft');
            $table->boolean('featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_sample')->default(false);
            $table->unsignedInteger('lock_version')->default(0); // optimistic concurrency: bumped on every save
            $table->timestamps();
            $table->index(['status', 'sort_order']);
        });

        // Images and datasheets. Files live in storage/app/media/<storage_key>/…
        // (outside the web root) and are served by a controller that checks
        // whether the owning item is public.
        Schema::create('media_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kind', 10); // image | datasheet
            $table->string('storage_key', 100)->unique(); // <folder>/<owner uuid>/<asset uuid>
            $table->foreignUuid('package_id')->nullable()->constrained('packages')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('content_block_key', 60)->nullable();
            $table->foreign('content_block_key')->references('key')->on('content_blocks')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('alt', 300)->default('');
            $table->string('caption', 300)->nullable();
            $table->string('file_name', 200)->nullable();
            $table->json('widths');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('bytes')->nullable();
            $table->boolean('is_cover')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Quote requests: kept in the admin so nothing depends on email delivery.
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 30)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('inquiry_type', 40);
            $table->string('location', 120)->nullable();
            $table->string('bill_range', 20)->nullable();
            $table->string('item_type', 10)->nullable(); // package | product
            $table->string('item_slug', 80)->nullable();
            $table->string('item_label', 200)->nullable(); // resolved server-side at submission
            $table->text('message');
            $table->string('status', 10)->default('new'); // new | handled | archived
            $table->text('staff_notes')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['inquiries', 'media_assets', 'projects', 'products', 'packages', 'product_categories', 'content_blocks', 'site_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
