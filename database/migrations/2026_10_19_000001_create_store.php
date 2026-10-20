<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('customer');
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->json('legacy_data')->nullable();
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->string('image')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('sku')->index();
            $t->text('description');
            $t->decimal('price', 14, 2);
            $t->decimal('compare_price', 14, 2)->nullable();
            $t->integer('stock')->nullable();
            $t->boolean('active')->default(false);
            $t->boolean('featured')->default(false);
            $t->boolean('catalog_visible')->default(true);
            $t->unsignedInteger('age_min_months')->nullable();
            $t->unsignedInteger('age_max_months')->nullable();
            $t->string('age_source')->nullable();
            $t->text('safety')->nullable();
            $t->string('material')->nullable();
            $t->string('brand')->nullable();
            $t->string('source')->default('admin');
            $t->json('legacy_data')->nullable();
            $t->timestamps();
            $t->index(['active', 'catalog_visible', 'price']);
            $t->index(['age_min_months', 'age_max_months']);
        });
        Schema::create('category_product', function (Blueprint $t) {
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->primary(['category_id', 'product_id']);
        });
        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->integer('sort_order')->default(0);
        });
        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('subtitle')->nullable();
            $t->string('eyebrow')->nullable();
            $t->string('image')->nullable();
            $t->string('link')->default('/catalogo');
            $t->string('button')->default('Explorar juguetes');
            $t->string('theme')->default('main');
            $t->integer('sort_order')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });
        Schema::create('shipping_methods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->decimal('price', 12, 2);
            $t->boolean('active')->default(false);
            $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->uuid('public_token')->unique();
            $t->string('idempotency_key')->nullable()->unique();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('customer_name')->nullable();
            $t->string('customer_email')->nullable();
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->string('shipping_name')->nullable();
            $t->decimal('subtotal', 14, 2)->default(0);
            $t->decimal('shipping_total', 14, 2)->default(0);
            $t->decimal('total', 14, 2)->default(0);
            $t->string('currency')->default('PEN');
            $t->string('status')->default('pending');
            $t->string('payment_status')->default('pending');
            $t->string('payment_method')->default('coordination');
            $t->boolean('stock_reserved')->default(false);
            $t->boolean('stock_released')->default(false);
            $t->timestamp('confirmation_sent_at')->nullable();
            $t->string('source')->default('laravel');
            $t->json('legacy_data')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('sku')->nullable();
            $t->decimal('quantity', 14, 2);
            $t->decimal('unit_price', 14, 2);
            $t->decimal('total', 14, 2);
            $t->json('legacy_data')->nullable();
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $t->integer('before')->nullable();
            $t->integer('change');
            $t->integer('after');
            $t->string('reason');
            $t->timestamps();
        });
        Schema::create('order_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('event');
            $t->text('note')->nullable();
            $t->timestamps();
        });
        Schema::create('legacy_records', function (Blueprint $t) {
            $t->id();
            $t->string('source_table');
            $t->unsignedBigInteger('source_id');
            $t->json('payload');
            $t->unique(['source_table', 'source_id']);
        });
        Schema::create('payment_events', function (Blueprint $t) {
            $t->id();
            $t->string('provider');
            $t->string('external_id');
            $t->foreignId('order_id')->nullable()->constrained();
            $t->json('payload');
            $t->timestamps();
            $t->unique(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        foreach (['payment_events', 'legacy_records', 'order_events', 'stock_movements', 'order_items', 'orders', 'shipping_methods', 'settings', 'banners', 'product_images', 'category_product', 'products', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'phone', 'address', 'legacy_data']));
    }
};
