<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'shipping_methods'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->boolean('demo_data')->default(false));
        }Schema::table('orders', fn (Blueprint $t) => $t->boolean('is_demo')->default(false));
    }

    public function down(): void
    {
        foreach (['products', 'shipping_methods'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('demo_data'));
        }Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('is_demo'));
    }
};
