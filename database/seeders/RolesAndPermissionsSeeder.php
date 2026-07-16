<?php

namespace Database\Seeders;

use App\Constants\Permisos;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $todosLosPermisos = [
            Permisos::USUARIOS_VER,
            Permisos::USUARIOS_GESTIONAR,
            Permisos::EMPLEADOS_VER,
            Permisos::EMPLEADOS_GESTIONAR,
        ];

        collect($todosLosPermisos)->each(
            fn ($permiso) => Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ])
        );

        // Admin: acceso general
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions($todosLosPermisos);

        // RRHH: gestiona empleados y otorga accesos (con restricción en código, no en permiso)
        $rrhh = Role::firstOrCreate(['name' => 'RRHH', 'guard_name' => 'web']);
        $rrhh->syncPermissions([
            Permisos::EMPLEADOS_VER,
            Permisos::EMPLEADOS_GESTIONAR,
            Permisos::USUARIOS_VER,
            Permisos::USUARIOS_GESTIONAR,
        ]);

        // Contable: solo ve datos de personal (planilla, costos de mano de obra)
        $contable = Role::firstOrCreate(['name' => 'Contable', 'guard_name' => 'web']);
        $contable->syncPermissions([
            Permisos::EMPLEADOS_VER,
        ]);

        // Consulta: auditor / supervisor, solo lectura de todo el dominio personal
        $consulta = Role::firstOrCreate(['name' => 'Consulta', 'guard_name' => 'web']);
        $consulta->syncPermissions([
            Permisos::EMPLEADOS_VER,
            Permisos::USUARIOS_VER,
        ]);
    }
}