<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Registra una acción crítica. Guarda quién, cuándo, desde dónde,
     * el motivo y una foto de los datos antes/después.
     */
    public static function log(
        string $event,
        string $description,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        ?string $reason = null,
        string $severity = AuditLog::SEVERITY_INFO,
    ): AuditLog {
        $user = Auth::user();
        $inConsole = app()->runningInConsole() && !app()->runningUnitTests();

        return AuditLog::create([
            'event' => $event,
            'severity' => $severity,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'description' => mb_substr($description, 0, 255),
            'reason' => $reason,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'user_id' => $user?->id,
            'user_name' => $user
                ? ($user->person?->display_name ?? $user->email)
                : ($inConsole ? 'Sistema (consola)' : null),
            'ip_address' => $inConsole ? null : request()->ip(),
            'user_agent' => $inConsole ? null : mb_substr((string) request()->userAgent(), 0, 255),
        ]);
    }

    /** Solo los campos que cambiaron, para no llenar la bitácora de ruido. */
    public static function diff(array $before, array $after): array
    {
        $old = [];
        $new = [];

        foreach ($after as $key => $value) {
            if (($before[$key] ?? null) != $value) {
                $old[$key] = $before[$key] ?? null;
                $new[$key] = $value;
            }
        }

        return [$old, $new];
    }
}
