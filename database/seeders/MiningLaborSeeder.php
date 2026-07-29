<?php

namespace Database\Seeders;

use App\Models\MiningLabor;
use Illuminate\Database\Seeder;

class MiningLaborSeeder extends Seeder
{
    public function run(): void
    {
        $labors = [
            ['code' => 'TJ 138 RUBY', 'labor_type' => 'tajo', 'vein_name' => 'RUBY', 'level_number' => 138],
            ['code' => 'TJ 88 NELLY', 'labor_type' => 'tajo', 'vein_name' => 'NELLY', 'level_number' => 88],
            ['code' => 'S/N 198 RUBY', 'labor_type' => 'subnivel', 'vein_name' => 'RUBY', 'level_number' => 198],
            ['code' => 'EST 138 RUBY', 'labor_type' => 'estocada', 'vein_name' => 'RUBY', 'level_number' => 138],
            ['code' => 'S/N 178 RUBY', 'labor_type' => 'subnivel', 'vein_name' => 'RUBY', 'level_number' => 178],
            ['code' => 'S/N 138 RUBY', 'labor_type' => 'subnivel', 'vein_name' => 'RUBY', 'level_number' => 138],
            ['code' => 'GAL 84 NELLY', 'labor_type' => 'galeria', 'vein_name' => 'NELLY', 'level_number' => 84],
            ['code' => 'S/N 218 LIDIA', 'labor_type' => 'subnivel', 'vein_name' => 'LIDIA', 'level_number' => 218],
            ['code' => 'GAL 214 LIDIA', 'labor_type' => 'galeria', 'vein_name' => 'LIDIA', 'level_number' => 214],
            ['code' => 'S/N 158 KATY', 'labor_type' => 'subnivel', 'vein_name' => 'KATY', 'level_number' => 158],
            ['code' => 'TJ 98 NELLY', 'labor_type' => 'tajo', 'vein_name' => 'NELLY', 'level_number' => 98],
            ['code' => 'CH 935 NELLY', 'labor_type' => 'chimenea', 'vein_name' => 'NELLY', 'level_number' => 935],
            ['code' => 'GAL 154 KATY', 'labor_type' => 'galeria', 'vein_name' => 'KATY', 'level_number' => 154],
            ['code' => 'PQ 8', 'labor_type' => 'pique', 'vein_name' => null, 'level_number' => 8],
            ['code' => 'CH 194 RUBY', 'labor_type' => 'chimenea', 'vein_name' => 'RUBY', 'level_number' => 194],
            ['code' => 'CX 134 LIDIA', 'labor_type' => 'crucero', 'vein_name' => 'LIDIA', 'level_number' => 134],
            ['code' => 'TJ 144 ELY', 'labor_type' => 'tajo', 'vein_name' => 'ELY', 'level_number' => 144],
            ['code' => 'S/N 174 CAROLINA', 'labor_type' => 'subnivel', 'vein_name' => 'CAROLINA', 'level_number' => 174],
            ['code' => 'TJ 138 LIDIA', 'labor_type' => 'tajo', 'vein_name' => 'LIDIA', 'level_number' => 138],
            ['code' => 'TJ 218 LIDIA', 'labor_type' => 'tajo', 'vein_name' => 'LIDIA', 'level_number' => 218],
            ['code' => 'TJ 138 CERO', 'labor_type' => 'tajo', 'vein_name' => 'CERO', 'level_number' => 138],
            ['code' => 'TJ 118 RUBY', 'labor_type' => 'tajo', 'vein_name' => 'RUBY', 'level_number' => 118],
            ['code' => 'GAL 174 CAROLINA', 'labor_type' => 'galeria', 'vein_name' => 'CAROLINA', 'level_number' => 174],
            ['code' => 'B/C 278 CAROLINA', 'labor_type' => 'buzon', 'vein_name' => 'CAROLINA', 'level_number' => 278],
            ['code' => 'CH 154 KATY', 'labor_type' => 'chimenea', 'vein_name' => 'KATY', 'level_number' => 154],
            ['code' => 'CH 118 LIDIA', 'labor_type' => 'chimenea', 'vein_name' => 'LIDIA', 'level_number' => 118],
            ['code' => 'S/N 138 CERO', 'labor_type' => 'subnivel', 'vein_name' => 'CERO', 'level_number' => 138],
            ['code' => 'B/C 930 RUBY', 'labor_type' => 'buzon', 'vein_name' => 'RUBY', 'level_number' => 930],
            ['code' => 'TJ 174 CAROLINA', 'labor_type' => 'tajo', 'vein_name' => 'CAROLINA', 'level_number' => 174],
            ['code' => 'TJ 118 NELLY', 'labor_type' => 'tajo', 'vein_name' => 'NELLY', 'level_number' => 118],
            ['code' => 'S/N 194 RUBY', 'labor_type' => 'subnivel', 'vein_name' => 'RUBY', 'level_number' => 194],
            ['code' => 'GAL 174 RUBY', 'labor_type' => 'galeria', 'vein_name' => 'RUBY', 'level_number' => 174],
            ['code' => 'CX 144 RUBY', 'labor_type' => 'crucero', 'vein_name' => 'RUBY', 'level_number' => 144],
            ['code' => 'PQ 9 NELLY', 'labor_type' => 'pique', 'vein_name' => 'NELLY', 'level_number' => 9],
        ];

        foreach ($labors as $labor) {
            MiningLabor::updateOrCreate(
                ['code' => $labor['code']],
                [
                    'labor_type' => $labor['labor_type'],
                    'vein_name' => $labor['vein_name'],
                    'level_number' => $labor['level_number'],
                    'direction' => null,
                    'status' => 'active',
                    'notes' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}