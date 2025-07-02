<?php

namespace Database\Seeders;

use Modules\Auth\Models\AuthUser;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\PengajuanSurat\Database\Seeders\PengajuanSuratDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RolePermissionSeeder::class,
            AuthDatabaseSeeder::class,
            PengajuanSuratDatabaseSeeder::class,
        ]);
    }
}
