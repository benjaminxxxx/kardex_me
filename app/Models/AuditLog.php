<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de auditoría. Es de solo escritura: no se puede editar ni borrar.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    /** Etiquetas legibles para el listado y los filtros */
    public const EVENTS = [
        'purchase.deleted' => 'Compra eliminada',
        'purchase.restored' => 'Compra restaurada',
        'purchase.updated' => 'Compra editada',
        'kardex.deleted' => 'Kárdex eliminado',
        'kardex.closed' => 'Kárdex cerrado',
        'stock_movement.created' => 'Ajuste manual de stock',
        'stock_movement.deleted' => 'Movimiento de stock eliminado',
        'stock.data_fix' => 'Corrección de stock (migración)',
        'stock.review_required' => 'Stock requiere revisión',
        'user.access_created' => 'Acceso creado',
        'user.access_removed' => 'Acceso eliminado',
        'user.password_changed' => 'Contraseña cambiada',
        'user.email_changed' => 'Correo de acceso cambiado',
        'user.role_changed' => 'Rol de usuario cambiado',
        'role.permissions_changed' => 'Permisos de rol cambiados',
        'role.deleted' => 'Rol eliminado',
        'settings.updated' => 'Configuración modificada',
        'product.deleted' => 'Producto eliminado',
        'supplier.deleted' => 'Proveedor dado de baja',
        'supplier.contacts_changed' => 'Direcciones/cuentas de proveedor',
        'kardex.opening_changed' => 'Saldo inicial de kárdex editado',
        'mining_labor.force_deleted' => 'Labor eliminada definitivamente',
    ];

    protected $fillable = [
        'event',
        'severity',
        'auditable_type',
        'auditable_id',
        'description',
        'reason',
        'old_values',
        'new_values',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Los registros de auditoría no se pueden modificar.'));
        static::deleting(fn () => throw new \LogicException('Los registros de auditoría no se pueden eliminar.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEventLabelAttribute(): string
    {
        return self::EVENTS[$this->event] ?? $this->event;
    }
}
