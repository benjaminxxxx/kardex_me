<?php

namespace Database\Seeders;

use App\Constants\Permisos;
use App\Services\PermisosServicio;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Sembramos todos los permisos definidos en el árbol de una sola vez,
        // usando el mismo servicio que usa el botón manual de sincronización.
        PermisosServicio::sincronizarPermisosEnBD();

        // ===== Admin: acceso general a todo =====
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            Permisos::USUARIOS_VER, Permisos::USUARIOS_GESTIONAR,
            Permisos::ROLES_VER, Permisos::ROLES_GESTIONAR, Permisos::PERMISOS_SINCRONIZAR,
            Permisos::CONFIGURACION_VER, Permisos::CONFIGURACION_GESTIONAR,
            Permisos::EMPLEADOS_VER, Permisos::EMPLEADOS_GESTIONAR,
            Permisos::PROVEEDORES_VER, Permisos::PROVEEDORES_GESTIONAR,
            Permisos::COMPRAS_VER, Permisos::COMPRAS_GESTIONAR,
            Permisos::PRODUCTOS_VER, Permisos::PRODUCTOS_GESTIONAR,
            Permisos::DESPACHOS_EXPLOSIVOS_VER,Permisos::EXPLOSIVOS_DESPACHO_REGISTRAR,
            Permisos::EXPLOSIVOS_DISTRIBUIR,
            Permisos::DISTRIBUCION_REPORTE_VER,
            Permisos::LABORES_VER, Permisos::LABORES_GESTIONAR,
            Permisos::KARDEX_VER, Permisos::KARDEX_GESTIONAR,
        ]);

        // ===== RRHH: gestiona empleados y otorga accesos =====
        $rrhh = Role::firstOrCreate(['name' => 'RRHH', 'guard_name' => 'web']);
        $rrhh->syncPermissions([
            Permisos::EMPLEADOS_VER, Permisos::EMPLEADOS_GESTIONAR,
            Permisos::USUARIOS_VER, Permisos::USUARIOS_GESTIONAR,
        ]);

        // ===== Contable: solo ve datos de personal y compras (costos) =====
        $contable = Role::firstOrCreate(['name' => 'Contable', 'guard_name' => 'web']);
        $contable->syncPermissions([
            Permisos::EMPLEADOS_VER,
            Permisos::COMPRAS_VER,
            Permisos::PROVEEDORES_VER,
        ]);

        // ===== Consulta: auditor/supervisor, solo lectura =====
        $consulta = Role::firstOrCreate(['name' => 'Consulta', 'guard_name' => 'web']);
        $consulta->syncPermissions([
            Permisos::EMPLEADOS_VER,
            Permisos::USUARIOS_VER,
        ]);

        // ===== Despachador: almacenero, registra despacho de explosivos + entradas =====
        $despachador = Role::firstOrCreate(['name' => 'Despachador', 'guard_name' => 'web']);
        $despachador->syncPermissions([
            Permisos::DESPACHOS_EXPLOSIVOS_VER,
            Permisos::EXPLOSIVOS_DESPACHO_REGISTRAR,
            Permisos::PRODUCTOS_VER,
            Permisos::LABORES_VER,
        ]);

        // ===== Supervisor: retira material y distribuye por perforista/labor =====
        $supervisor = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => 'web']);
        $supervisor->syncPermissions([
            Permisos::DESPACHOS_EXPLOSIVOS_VER,
            Permisos::EXPLOSIVOS_DISTRIBUIR,
            Permisos::DISTRIBUCION_REPORTE_VER,
            Permisos::LABORES_VER,
        ]);

        // Developer se crea sin permisos explícitos — el Gate::before lo cubre
        Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);
    }
}