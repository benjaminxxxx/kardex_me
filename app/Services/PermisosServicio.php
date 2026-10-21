<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisosServicio
{
    public static function aplanarArbol(array $nodos): array
    {
        $nombres = [];
        foreach ($nodos as $nodo) {
            $nombres[] = $nodo['nombre'];
            if (!empty($nodo['hijos'])) {
                $nombres = array_merge($nombres, self::aplanarArbol($nodo['hijos']));
            }
        }
        return $nombres;
    }

    /**
     * Único punto donde se crean/eliminan permisos en BD.
     * Se dispara solo desde el botón manual "Sincronizar permisos",
     * nunca automáticamente en cada request.
     */
    public static function sincronizarPermisosEnBD(): array
    {
        $arbol = config('permisos_tree');
        $nombresEnArbol = self::aplanarArbol($arbol);

        $creados = 0;
        foreach ($nombresEnArbol as $nombre) {
            $permiso = Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
            if ($permiso->wasRecentlyCreated) {
                $creados++;
            }
        }

        $eliminados = Permission::whereNotIn('name', $nombresEnArbol)->get();
        $totalEliminados = $eliminados->count();
        foreach ($eliminados as $permiso) {
            $permiso->delete(); // Spatie limpia role_has_permissions / model_has_permissions
        }

        return ['creados' => $creados, 'eliminados' => $totalEliminados];
    }

    /**
     * Solo asigna permisos YA EXISTENTES en BD a un rol.
     * No crea nada — si el árbol tiene un permiso nuevo que aún no
     * se sincronizó, simplemente no se podrá asignar hasta sincronizar.
     */
    public static function guardarPermisosParaRol(int $roleId, array $permisosActivados): void
    {
        $rol = Role::findOrFail($roleId);

        $existentes = Permission::whereIn('name', $permisosActivados)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->toArray();

        $antes = $rol->permissions->pluck('name')->all();
        $rol->syncPermissions($existentes);

        $agregados = array_values(array_diff($existentes, $antes));
        $quitados = array_values(array_diff($antes, $existentes));

        if ($agregados || $quitados) {
            AuditLogger::log(
                'role.permissions_changed',
                "Permisos del rol {$rol->name} modificados (+" . count($agregados) . ' / -' . count($quitados) . ')',
                $rol,
                old: ['quitados' => $quitados],
                new: ['agregados' => $agregados],
                severity: \App\Models\AuditLog::SEVERITY_WARNING,
            );
        }
    }

    public static function obtenerPermisosDeRol(int $roleId): array
    {
        $rol = Role::findOrFail($roleId);
        return $rol->permissions->pluck('name')->toArray();
    }
}