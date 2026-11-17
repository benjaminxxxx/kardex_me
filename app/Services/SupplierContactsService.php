<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Supplier;
use App\Models\SupplierBankAccount;
use App\Models\SupplierBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sincroniza las direcciones y cuentas bancarias de un proveedor.
 * Actualiza las existentes (conservan su id), crea las nuevas y elimina
 * solo las que se quitaron. Los cambios quedan en Auditoría.
 */
class SupplierContactsService
{
    private const BRANCH_FIELDS = ['type', 'name', 'is_main', 'country', 'state', 'city', 'address', 'phone', 'email'];

    private const ACCOUNT_FIELDS = [
        'type', 'bank_name', 'account_number', 'cci', 'currency',
        'wallet_provider', 'wallet_phone', 'account_holder_name', 'is_main',
    ];

    public function sync(Supplier $supplier, array $branches, array $bankAccounts, bool $audit = true): void
    {
        // Las cuentas sin número, banco ni billetera se ignoran (filas vacías del formulario)
        $bankAccounts = array_filter($bankAccounts, fn($a) =>
            filled($a['account_number'] ?? null) || filled($a['bank_name'] ?? null) || filled($a['wallet_phone'] ?? null));

        $changes = [
            'direcciones' => $this->syncRelation($supplier->branches(), $branches, self::BRANCH_FIELDS),
            'cuentas_bancarias' => $this->syncRelation($supplier->bankAccounts(), $bankAccounts, self::ACCOUNT_FIELDS),
        ];

        $changes = array_filter(array_map('array_filter', $changes));

        if ($audit && $changes) {
            AuditLogger::log(
                'supplier.contacts_changed',
                "Direcciones/cuentas del proveedor {$supplier->person?->display_name} modificadas",
                $supplier,
                new: $changes,
                severity: isset($changes['cuentas_bancarias']) ? AuditLog::SEVERITY_WARNING : AuditLog::SEVERITY_INFO,
            );
        }
    }

    /** @return array{agregadas: array, modificadas: array, eliminadas: array} */
    private function syncRelation(HasMany $relation, array $rows, array $fields): array
    {
        $existing = $relation->get()->keyBy('id');
        $result = ['agregadas' => [], 'modificadas' => [], 'eliminadas' => []];
        $kept = [];

        foreach ($rows as $row) {
            $data = [];
            foreach ($fields as $field) {
                $value = $row[$field] ?? null;
                $data[$field] = $value === '' ? null : $value;
            }

            $model = !empty($row['id']) ? $existing->get($row['id']) : null;

            if ($model) {
                $before = $this->only($model, $fields);
                $model->update($data);
                $kept[] = $model->id;

                [$old, $new] = AuditLogger::diff($before, $this->only($model->refresh(), $fields));
                if ($new) {
                    $result['modificadas'][] = ['id' => $model->id, 'antes' => $old, 'despues' => $new];
                }
            } else {
                $created = $relation->create($data);
                $kept[] = $created->id;
                $result['agregadas'][] = ['id' => $created->id] + array_filter($data, fn($v) => $v !== null);
            }
        }

        foreach ($existing->except($kept) as $removed) {
            $result['eliminadas'][] = ['id' => $removed->id] + array_filter($this->only($removed, $fields), fn($v) => $v !== null);
            $removed->delete();
        }

        return $result;
    }

    private function only(Model $model, array $fields): array
    {
        return collect($model->only($fields))
            ->map(fn($v) => is_bool($v) ? $v : ($v === '' ? null : $v))
            ->all();
    }
}
