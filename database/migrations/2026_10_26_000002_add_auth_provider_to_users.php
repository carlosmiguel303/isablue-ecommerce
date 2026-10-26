<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('password')->nullable()->change();
            if (! Schema::hasColumn('users', 'auth_provider')) {
                $t->string('auth_provider', 20)->default('local')->after('google_id');
            }
        });
        // Las cuentas existentes conservan su contraseña; solo se etiqueta el origen Google.
        DB::table('users')->whereNotNull('google_id')->update(['auth_provider' => 'google']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('auth_provider');
        });
    }
};
