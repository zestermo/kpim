<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed managers first (required for gameplay)
        $this->call([
            ManagerSeeder::class,
        ]);
    }
}
