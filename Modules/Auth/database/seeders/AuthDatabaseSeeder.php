<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\AuthUser;
use Spatie\Permission\Models\Role;

class AuthDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Staff Desa
        $staff = AuthUser::firstOrCreate(
            ['username' => 'staff01'],
            [
                'name' => 'Staff Desa 01',
                'password' => Hash::make('passwordstaff'),
            ]
        );
        $staff->assignRole('staff-desa');

        // Kepala Desa
        $kepdes = AuthUser::firstOrCreate(
            ['username' => 'kepdes01'],
            [
                'name' => 'Kepala Desa',
                'password' => Hash::make('passwordkepdes'),
            ]
        );
        $kepdes->assignRole('kepala-desa');
    }
}

