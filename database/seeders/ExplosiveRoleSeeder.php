<?php

namespace Database\Seeders;

use App\Models\ExplosiveRole;
use Illuminate\Database\Seeder;

class ExplosiveRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['code' => 'detonator', 'name' => 'Fulminante', 'sort_order' => 1],
            ['code' => 'charge', 'name' => 'Emulnor / Dinamita', 'sort_order' => 2],
            ['code' => 'safety_fuse', 'name' => 'Mecha Lenta', 'sort_order' => 3],
            ['code' => 'guide', 'name' => 'Guía', 'sort_order' => 4],
            ['code' => 'aux_guide', 'name' => 'Guía Auxiliar', 'sort_order' => 5],
            ['code' => 'bulk_explosive', 'name' => 'Anfo', 'sort_order' => 6],

        ];

        foreach ($roles as $role) {
            ExplosiveRole::updateOrCreate(
                ['code' => $role['code']],
                [
                    'name' => $role['name'],
                    'sort_order' => $role['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}