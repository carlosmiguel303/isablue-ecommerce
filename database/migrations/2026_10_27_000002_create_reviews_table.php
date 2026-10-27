<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->text('comment');
            $t->string('photo')->nullable();
            $t->string('author_name', 80);
            $t->string('district', 60)->nullable();
            $t->string('status', 15)->default('pending')->index();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
