<?php

namespace Database\Seeders;

use App\Models\User;
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
        // User::factory(10)->create();

        $this->call([
            CompanySettingSeeder::class,
            RolesAndPermissionsSeeder::class, // primero: permisos y roles deben existir
            DeveloperSeeder::class,           // luego: developer necesita el rol Developer ya creado
            EmployeeSeeder::class,            // al final: asigna roles ya sembrados
            UnitSeeder::class,
            ProductCategorySeeder::class,
            ExplosiveRoleSeeder::class,
            PermissionSeeder::class,
            WarehouseSeeder::class,
            ProductSeeder::class,
            SupplierSeeder::class,
            MiningLaborSeeder::class
        ]);
    }
}
