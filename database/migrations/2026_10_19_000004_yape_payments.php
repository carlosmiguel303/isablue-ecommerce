<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->timestamp('paid_at')->nullable()->after('payment_method');
            $t->string('payment_reference', 60)->nullable()->after('paid_at');
            $t->string('voucher_path')->nullable()->after('payment_reference');
            $t->timestamp('voucher_uploaded_at')->nullable()->after('voucher_path');
            $t->text('customer_note')->nullable()->after('address');
            $t->index(['payment_status', 'paid_at']);
        });

        // Pedidos ya pagados antes de este cambio: su stock se descontó al crearlos.
        DB::table('orders')->where('payment_status', 'paid')->whereNull('paid_at')->update(['paid_at' => DB::raw('updated_at')]);
        DB::table('orders')->where('source', 'laravel')->where('payment_method', 'coordination')->update(['payment_method' => 'yape']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropIndex(['payment_status', 'paid_at']);
            $t->dropColumn(['paid_at', 'payment_reference', 'voucher_path', 'voucher_uploaded_at', 'customer_note']);
        });
    }
};
