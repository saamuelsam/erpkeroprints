<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@keroprints.local'],
            [
                'name'     => 'Administrador',
                'email'    => 'admin@keroprints.local',
                'password' => Hash::make('Admin@12345'),
            ]
        );
    }
}
