<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['4A', 'BOBINAS'],
            ['BJ', 'BALDE'],
            ['BLL', 'BARRILES'],
            ['BG', 'BOLSA'],
            ['BO', 'BOTELLAS'],
            ['BX', 'CAJA'],
            ['CT', 'CARTONES'],
            ['CMK', 'CENTIMETRO CUADRADO'],
            ['CMQ', 'CENTIMETRO CUBICO'],
            ['CMT', 'CENTIMETRO LINEAL'],
            ['CEN', 'CIENTO DE UNIDADES'],
            ['CY', 'CILINDRO'],
            ['CJ', 'CONOS'],
            ['DZN', 'DOCENA'],
            ['DZP', 'DOCENA POR 10**6'],
            ['BE', 'FARDO'],
            ['GLI', 'GALON INGLES (4,545956L)'],
            ['GRM', 'GRAMO'],
            ['GRO', 'GRUESA'],
            ['HLT', 'HECTOLITRO'],
            ['LEF', 'HOJA'],
            ['SET', 'JUEGO'],
            ['KGM', 'KILOGRAMO'],
            ['KTM', 'KILOMETRO'],
            ['KWH', 'KILOVATIO HORA'],
            ['KT', 'KIT'],
            ['CA', 'LATAS'],
            ['LBR', 'LIBRAS'],
            ['LTR', 'LITRO'],
            ['MWH', 'MEGAWATT HORA'],
            ['MTR', 'METRO'],
            ['MTK', 'METRO CUADRADO'],
            ['MTQ', 'METRO CUBICO'],
            ['MGM', 'MILIGRAMOS'],
            ['MLT', 'MILILITRO'],
            ['MMT', 'MILIMETRO'],
            ['MMK', 'MILIMETRO CUADRADO'],
            ['MMQ', 'MILIMETRO CUBICO'],
            ['MLL', 'MILLARES'],
            ['UM', 'MILLON DE UNIDADES'],
            ['ONZ', 'ONZAS'],
            ['PF', 'PALETAS'],
            ['PK', 'PAQUETE'],
            ['PR', 'PAR'],
            ['FOT', 'PIES'],
            ['FTK', 'PIES CUADRADOS'],
            ['FTQ', 'PIES CUBICOS'],
            ['C62', 'PIEZAS'],
            ['PG', 'PLACAS'],
            ['ST', 'PLIEGO'],
            ['INH', 'PULGADAS'],
            ['RM', 'RESMA'],
            ['DR', 'TAMBOR'],
            ['STN', 'TONELADA CORTA'],
            ['LTN', 'TONELADA LARGA'],
            ['TNE', 'TONELADAS'],
            ['TU', 'TUBOS'],
            ['NIU', 'UNIDAD (BIENES)'],
            ['ZZ', 'UNIDAD (SERVICIOS)'],
            ['GLL', 'US GALON (3,7843 L)'],
            ['YRD', 'YARDA'],
            ['YDK', 'YARDA CUADRADA'],
        ];

        // Solo activamos y ponemos alias a las que realmente vas a usar al inicio
        // (explosivos, combustible, insumos generales). El resto queda sembrado
        // pero inactivo, para no saturar el select — se activan cuando se necesiten.
        $aliases = [
            'NIU' => 'u',
            'KGM' => 'kg',
            'GRM' => 'g',
            'MTR' => 'm',
            'LTR' => 'L',
            'BX' => 'caja',
            'BG' => 'bolsa',
            'CY' => 'cilindro',
            'GLI' => 'gal',
        ];

        foreach ($units as [$code, $name]) {
            Unit::firstOrCreate(
                ['sunat_code' => $code],
                [
                    'name' => $name,
                    'alias' => $aliases[$code] ?? null,
                    'is_active' => array_key_exists($code, $aliases),
                ]
            );
        }
    }
}
