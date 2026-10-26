<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->string('slug', 220)->unique();
            $t->string('excerpt', 300)->nullable();
            $t->longText('body');
            $t->string('image')->nullable();
            $t->boolean('published')->default(false);
            $t->timestamp('published_at')->nullable()->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
