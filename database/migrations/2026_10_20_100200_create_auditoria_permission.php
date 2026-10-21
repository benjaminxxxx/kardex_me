<?php

use App\Constants\Permisos;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea el permiso del menú Auditoría y lo asigna al rol Admin,
 * para no depender del botón manual "Sincronizar permisos" en el servidor.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => Permisos::AUDITORIA_VER,
            'guard_name' => 'web',
        ]);

        Role::where('name', 'Admin')->where('guard_name', 'web')->first()
            ?->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', Permisos::AUDITORIA_VER)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
