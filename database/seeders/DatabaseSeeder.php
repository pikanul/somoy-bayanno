<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(DemoNewsContentSeeder::class);
        $this->call(CorrespondentSeeder::class);
        $this->call(LiveStreamSeeder::class);
        $this->call(EpaperSeeder::class);
    }
}
