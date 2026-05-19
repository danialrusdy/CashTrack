<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@cashtrack.test'],
            [
                'name'     => 'Admin CashTrack',
                'password' => Hash::make('password'),
            ]
        );

        $this->call(TransactionSeeder::class);
    }
}
