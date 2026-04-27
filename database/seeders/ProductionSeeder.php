<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Minimal data for a fresh production install:  make prod-seed
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
