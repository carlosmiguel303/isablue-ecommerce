<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('google_id')->nullable()->unique();
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->timestamp('reserved_until')->nullable();
        });
        Schema::create('integration_settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->longText('value');
            $t->timestamps();
        });
        Schema::create('mail_outbox', function (Blueprint $t) {
            $t->id();
            $t->string('event_key')->unique();
            $t->string('kind', 40);
            $t->string('recipient');
            $t->longText('payload');
            $t->unsignedInteger('attempts')->default(0);
            $t->timestamp('available_at')->index();
            $t->timestamp('locked_at')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->string('last_error')->nullable();
            $t->timestamps();
        });
        Schema::create('stock_holds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('quantity');
            $t->timestamp('expires_at')->index();
            $t->timestamp('released_at')->nullable();
            $t->timestamps();
            $t->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_holds');
        Schema::dropIfExists('mail_outbox');
        Schema::dropIfExists('integration_settings');
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('reserved_until'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('google_id'));
    }
};
