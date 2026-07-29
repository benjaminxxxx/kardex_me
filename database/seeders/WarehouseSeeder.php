<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $office = Warehouse::updateOrCreate(
            ['code' => 'OFFICE'],
            [
                'name' => 'Oficina',
                'is_active' => true,
            ]
        );

        $mine = Warehouse::updateOrCreate(
            ['code' => 'MINE'],
            [
                'name' => 'Mina',
                'is_active' => true,
            ]
        );

        $reception = Warehouse::updateOrCreate(
            ['code' => 'RECEPTION'],
            [
                'name' => 'Recepción',
                'is_active' => true,
            ]
        );

        $company = CompanySetting::first();

        if ($company) {
            $company->update([
                'purchase_default_warehouse_id' => $office->id,
                'mine_dispatch_warehouse_id' => $mine->id,
                'reception_warehouse_id' => $reception->id,
            ]);
        }
    }
}