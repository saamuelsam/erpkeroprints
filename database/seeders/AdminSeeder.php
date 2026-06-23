<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'keroprints@.com'],
            [
                'name'     => 'Administrador',
                'email'    => 'keroprints@.com',
                'password' => Hash::make('Keroprints@77'),
                'perfil'   => 'admin',
            ]
        );
    }
}
