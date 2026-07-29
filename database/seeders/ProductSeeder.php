<?php

namespace Database\Seeders;

use App\Models\ExplosiveRole;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPresentation;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $explosiveCategory = ProductCategory::where('code', 'EXP')->firstOrFail();

        $unit = Unit::where('sunat_code', 'NIU')->firstOrFail();
        $meter = Unit::where('sunat_code', 'MTR')->firstOrFail();
        $kg = Unit::where('sunat_code', 'KGM')->firstOrFail();
        $box = Unit::where('sunat_code', 'BX')->firstOrFail();

        $roles = ExplosiveRole::pluck('id', 'code');

        $products = [

            // ============================
            // FULMINANTES
            // ============================

            [
                'code' => 'FUL001',
                'name' => 'Fulminante',
                'chemical_name' => null,
                'brand' => 'FAMESA',
                'unit_id' => $unit->id,
                'role' => 'detonator',
                'presentations' => [
                    ['Unidad', $unit->id, 1, true],
                    ['Caja', $box->id, 100, false],
                ],
            ],

            [
                'code' => 'FUL002',
                'name' => 'Fulminante N',
                'chemical_name' => null,
                'brand' => 'FAMESA',
                'unit_id' => $unit->id,
                'role' => 'detonator',
                'presentations' => [
                    ['Unidad', $unit->id, 1, true],
                    ['Caja', $box->id, 100, false],
                ],
            ],

            // ============================
            // DINAMITA
            // ============================

            [
                'code' => 'DIN001',
                'name' => 'Dinamita',
                'chemical_name' => 'Emulnor',
                'brand' => 'FAMESA',
                'unit_id' => $unit->id,
                'role' => 'charge',
                'presentations' => [
                    ['Unidad', $unit->id, 1, true],
                    ['Caja', $box->id, 310, false],
                ],
            ],

            [
                'code' => 'DIN002',
                'name' => 'Dinamita N',
                'chemical_name' => 'Emulnor',
                'brand' => 'FAMESA',
                'unit_id' => $unit->id,
                'role' => 'charge',
                'presentations' => [
                    ['Unidad', $unit->id, 1, true],
                    ['Caja', $box->id, 310, false],
                ],
            ],

            // ============================
            // MECHA
            // ============================

            [
                'code' => 'MEC001',
                'name' => 'Mecha Lenta',
                'chemical_name' => null,
                'brand' => 'FAMESA',
                'unit_id' => $meter->id,
                'role' => 'safety_fuse',
                'presentations' => [
                    ['Metro', $meter->id, 1, true],
                    ['Rollo', $meter->id, 500, false],
                    ['Caja', $box->id, 1000, false],
                ],
            ],

            [
                'code' => 'MEC002',
                'name' => 'Mecha Lenta N',
                'chemical_name' => null,
                'brand' => 'FAMESA',
                'unit_id' => $meter->id,
                'role' => 'safety_fuse',
                'presentations' => [
                    ['Metro', $meter->id, 1, true],
                    ['Rollo', $meter->id, 500, false],
                    ['Caja', $box->id, 1000, false],
                ],
            ],

            // ============================
            // GUIA
            // ============================

            [
                'code' => 'GUI001',
                'name' => 'Guía',
                'chemical_name' => null,
                'brand' => 'FAMESA',
                'unit_id' => $meter->id,
                'role' => 'guide',
                'presentations' => [
                    ['Metro', $meter->id, 1, true],
                    ['Rollo', $meter->id, 500, false],
                ],
            ],

            [
                'code' => 'GUI002',
                'name' => 'Guía N',
                'chemical_name' => null,
                'brand' => 'FAMESA',
                'unit_id' => $meter->id,
                'role' => 'guide',
                'presentations' => [
                    ['Metro', $meter->id, 1, true],
                    ['Rollo', $meter->id, 500, false],
                ],
            ],

            // ============================
            // GUIA AUXILIAR
            // ============================

            [
                'code' => 'GUA001',
                'name' => 'Guía Auxiliar',
                'chemical_name' => null,
                'brand' => 'FAMESA',
                'unit_id' => $meter->id,
                'role' => 'aux_guide',
                'presentations' => [
                    ['Metro', $meter->id, 1, true],
                    ['Rollo', $meter->id, 500, false],
                ],
            ],

            [
                'code' => 'GUA002',
                'name' => 'Guía Auxiliar N',
                'chemical_name' => null,
                'brand' => 'FAMESA',
                'unit_id' => $meter->id,
                'role' => 'aux_guide',
                'presentations' => [
                    ['Metro', $meter->id, 1, true],
                    ['Rollo', $meter->id, 500, false],
                ],
            ],

            // ============================
            // ANFO
            // ============================

            [
                'code' => 'ANF001',
                'name' => 'ANFO',
                'chemical_name' => null,
                'brand' => 'FAMESA',
                'unit_id' => $kg->id,
                'role' => 'bulk_explosive',
                'presentations' => [
                    ['Kilogramo', $kg->id, 1, true],
                    ['Bolsa', $kg->id, 25, false],
                ],
            ],

            [
                'code' => 'ANF002',
                'name' => 'ANFO N',
                'chemical_name' => null,
                'brand' => 'FAMESA',
                'unit_id' => $kg->id,
                'role' => 'bulk_explosive',
                'presentations' => [
                    ['Kilogramo', $kg->id, 1, true],
                    ['Bolsa', $kg->id, 25, false],
                ],
            ],

        ];

        foreach ($products as $data) {

            $product = Product::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'chemical_name' => $data['chemical_name'],
                    'brand' => $data['brand'],
                    'category_id' => $explosiveCategory->id,
                    'unit_id' => $data['unit_id'],
                    'explosive_role_id' => $roles[$data['role']],
                    'is_active' => true,
                ]
            );

            foreach ($data['presentations'] as [$name, $unitId, $factor, $default]) {

                ProductPresentation::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'name' => $name,
                    ],
                    [
                        'unit_id' => $unitId,
                        'conversion_factor' => $factor,
                        'is_default_purchase' => $default,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}