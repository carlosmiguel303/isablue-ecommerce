<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'specifications')) {
            Schema::table('products', function (Blueprint $t) {
                $t->text('specifications')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->dropColumn('specifications');
        });
    }
};
