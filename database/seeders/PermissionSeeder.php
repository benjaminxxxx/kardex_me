<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Constants\Permisos;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // Usuarios
            Permisos::USUARIOS_VER,
            Permisos::USUARIOS_GESTIONAR,

            // Empleados
            Permisos::EMPLEADOS_VER,
            Permisos::EMPLEADOS_GESTIONAR,

            // Productos
            Permisos::PRODUCTOS_VER,
            Permisos::PRODUCTOS_GESTIONAR,

            // Proveedores
            Permisos::PROVEEDORES_VER,
            Permisos::PROVEEDORES_GESTIONAR,

            // Entradas
            Permisos::ENTRADAS_VER,
            Permisos::ENTRADAS_GESTIONAR,

            // Salidas
            Permisos::SALIDAS_VER,
            Permisos::SALIDAS_GESTIONAR,
            Permisos::DESPACHOS_EXPLOSIVOS_VER,
            Permisos::DESPACHOS_EXPLOSIVOS_GESTIONAR,
            Permisos::EXPLOSIVOS_RETIRAR,

            // Labores
            Permisos::LABORES_VER,
            Permisos::LABORES_GESTIONAR,

            // Kardex
            Permisos::KARDEX_VER,
            Permisos::KARDEX_GESTIONAR,
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}