<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $admin = DB::table('users')->where('email', 'keroprints@.com')->first()
            ?: DB::table('users')->where('email', 'admin@keroprints.local')->first();

        if (!$admin) {
            return;
        }

        DB::table('users')
            ->where('id', $admin->id)
            ->update([
                'email' => 'keroprints@.com',
                'password' => Hash::make('Keroprints@77'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $admin = DB::table('users')
            ->where('email', 'keroprints@.com')
            ->orderBy('id')
            ->first();

        if (!$admin) {
            return;
        }

        DB::table('users')
            ->where('id', $admin->id)
            ->update([
                'email' => 'admin@keroprints.local',
                'password' => Hash::make('Admin@12345'),
                'updated_at' => now(),
            ]);
    }
};
