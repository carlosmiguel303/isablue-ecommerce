<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('source', 'postgresql')->update(['currency' => 'UNKNOWN']);
    }

    public function down(): void
    {
        DB::table('orders')->where('source', 'postgresql')->update(['currency' => 'PEN']);
    }
};
