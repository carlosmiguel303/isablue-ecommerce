<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $t) {
            $t->string('category', 30)->default('novedades')->after('slug')->index();
            $t->string('video_url', 500)->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $t) {
            $t->dropColumn(['category', 'video_url']);
        });
    }
};
