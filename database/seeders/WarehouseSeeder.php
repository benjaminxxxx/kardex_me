<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'code' => 'OFFICE',
                'name' => 'Oficina',
            ],
            [
                'code' => 'MINE',
                'name' => 'Mina',
            ],
            [
                'code' => 'RECEPTION',
                'name' => 'Recepción',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['code' => $warehouse['code']],
                [
                    'name' => $warehouse['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}