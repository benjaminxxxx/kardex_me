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
                'children' => [
                    ['name' => 'Dinamita', 'code' => 'EXP-DIN'],
                    ['name' => 'ANFO', 'code' => 'EXP-ANF'],
                    ['name' => 'Emulsión', 'code' => 'EXP-EMU'],
                ],
            ],

            [
                'name' => 'Accesorios de Voladura',
                'code' => 'DET',
                'children' => [
                    ['name' => 'Fulminantes', 'code' => 'DET-FUL'],
                    ['name' => 'Mecha Lenta', 'code' => 'DET-MEL'],
                    ['name' => 'Guía', 'code' => 'DET-GUI'],
                    ['name' => 'Conectores', 'code' => 'DET-CON'],
                ],
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
                'name' => 'Herramientas',
                'code' => 'HER',
                'children' => [
                    ['name' => 'Herramientas Manuales', 'code' => 'HER-MAN'],
                    ['name' => 'Herramientas Eléctricas', 'code' => 'HER-ELE'],
                    ['name' => 'Instrumentos de Medición', 'code' => 'HER-MED'],
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
                'name' => 'Lubricantes',
                'code' => 'LUB',
                'children' => [
                    ['name' => 'Aceites', 'code' => 'LUB-ACE'],
                    ['name' => 'Grasas', 'code' => 'LUB-GRA'],
                    ['name' => 'Hidráulicos', 'code' => 'LUB-HID'],
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
                'name' => 'Material Eléctrico',
                'code' => 'ELE',
                'children' => [],
            ],

            [
                'name' => 'Ferretería',
                'code' => 'FER',
                'children' => [],
            ],

            [
                'name' => 'Seguridad Industrial',
                'code' => 'SEG',
                'children' => [],
            ],

            [
                'name' => 'Equipos y Maquinaria',
                'code' => 'MAQ',
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
                'name' => 'Comunicaciones',
                'code' => 'COMU',
                'children' => [],
            ],

            [
                'name' => 'Alimentos y Bebidas',
                'code' => 'ALI',
                'children' => [],
            ],

            [
                'name' => 'Medicamentos y Botiquín',
                'code' => 'MED',
                'children' => [],
            ],

            [
                'name' => 'Material de Laboratorio',
                'code' => 'LAB',
                'children' => [],
            ],

            [
                'name' => 'Reactivos Químicos',
                'code' => 'QUI',
                'children' => [],
            ],

            [
                'name' => 'Consumibles',
                'code' => 'CON',
                'children' => [],
            ],

            [
                'name' => 'Sacos y Empaques',
                'code' => 'SAC',
                'children' => [],
            ],

            [
                'name' => 'Mineral',
                'code' => 'MIN',
                'children' => [
                    ['name' => 'Mineral en Bruto', 'code' => 'MIN-BRU'],
                    ['name' => 'Mineral Clasificado', 'code' => 'MIN-CLA'],
                    ['name' => 'Concentrado', 'code' => 'MIN-CON'],
                ],
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