<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [

            [
                'name' => 'Explosivos',
                'code' => 'EXP',
                'children' => [],
            ],

            [
                'name' => 'Equipos de Protección Personal',
                'code' => 'EPP',
                'children' => [
                    ['name' => 'Protección de Cabeza', 'code' => 'EPP-CAB'],
                    ['name' => 'Protección Respiratoria', 'code' => 'EPP-RES'],
                    ['name' => 'Protección Auditiva', 'code' => 'EPP-AUD'],
                    ['name' => 'Protección Visual', 'code' => 'EPP-VIS'],
                    ['name' => 'Calzado', 'code' => 'EPP-CAL'],
                ],
            ],

            [
                'name' => 'Combustibles',
                'code' => 'COM',
                'children' => [
                    ['name' => 'Diésel', 'code' => 'COM-DIE'],
                    ['name' => 'Gasolina', 'code' => 'COM-GAS'],
                    ['name' => 'GLP', 'code' => 'COM-GLP'],
                ],
            ],

            [
                'name' => 'Repuestos',
                'code' => 'REP',
                'children' => [
                    ['name' => 'Motor', 'code' => 'REP-MOT'],
                    ['name' => 'Sistema Hidráulico', 'code' => 'REP-HID'],
                    ['name' => 'Perforación', 'code' => 'REP-PER'],
                ],
            ],

            [
                'name' => 'Ferretería',
                'code' => 'FER',
                'children' => [],
            ],

            [
                'name' => 'Oficina',
                'code' => 'OFI',
                'children' => [
                    ['name' => 'Papelería', 'code' => 'OFI-PAP'],
                    ['name' => 'Equipos', 'code' => 'OFI-EQP'],
                    ['name' => 'Consumibles de Impresión', 'code' => 'OFI-IMP'],
                ],
            ],

            [
                'name' => 'Limpieza',
                'code' => 'LIM',
                'children' => [],
            ],

            [
                'name' => 'Equipos Informáticos',
                'code' => 'TEC',
                'children' => [],
            ],

            [
                'name' => 'Alimentos y Bebidas',
                'code' => 'ALI',
                'children' => [],
            ],
            [
                'name' => 'Otros',
                'code' => 'OTR',
                'children' => [],
            ],

        ];

        foreach ($categories as $category) {

            $parent = ProductCategory::create([
                'parent_id' => null,
                'name' => $category['name'],
                'code' => $category['code'],
                'is_active' => true,
            ]);

            foreach ($category['children'] as $child) {
                ProductCategory::create([
                    'parent_id' => $parent->id,
                    'name' => $child['name'],
                    'code' => $child['code'],
                    'is_active' => true,
                ]);
            }
        }
    }
}