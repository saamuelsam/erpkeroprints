<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'perfil')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('perfil', 30)->default('atendente')->after('password');
            });
        }

        DB::table('users')
            ->where('email', 'keroprints@.com')
            ->update([
                'perfil' => 'admin',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'perfil')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('perfil');
            });
        }
    }
};
