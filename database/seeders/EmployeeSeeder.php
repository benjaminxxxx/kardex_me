<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    private int $sequence = 2; // 1 ya lo usa el Developer (DEV000001)

    public function run(): void
    {
        $this->createStaff('RRHH', '76543211', 'Rosa', 'Delgado', 'Ccama', 'rosa.rrhh@minera.local', 'RRHH');
        $this->createStaff('Admin', '76543212', 'Carlos', 'Fernandez', 'Zapata', 'carlos.admin@minera.local', 'Admin');
        $this->createStaff('Despachador', '76543213', 'Marco', 'Huaman', 'Rios', 'marco.despacho@minera.local', 'Despachador');

        $this->createStaff('Supervisor', '76543214', 'Luis', 'Chura', 'Mamani', 'luis.supervisor@minera.local', 'Supervisor');
        $this->createStaff('Supervisor', '76543215', 'Pedro', 'Vilca', 'Huaraca', 'pedro.supervisor@minera.local', 'Supervisor');

        // Perforistas: SIN cuenta de acceso (solo Person + Employee)
        $perforistas = [
            ['77111001', 'Wilber', 'Taype', 'Quispe'],
            ['77111002', 'Jorge', 'Asto', 'Ramos'],
            ['77111003', 'Antonio', 'Flores', 'Ccahuana'],
            ['77111004', 'Emilio', 'Alvaro', 'Ticona'],
            ['77111005', 'Jaime', 'Perez', 'Sucaticona'],
        ];

        foreach ($perforistas as [$dni, $nombres, $apPat, $apMat]) {
            $this->createDrillerWithoutAccess($dni, $nombres, $apPat, $apMat);
        }
    }

    private function nextEmployeeCode(): string
    {
        return 'EMP' . str_pad((string) $this->sequence++, 6, '0', STR_PAD_LEFT);
    }

    private function createStaff(
        string $label, string $dni, string $nombres, string $apPat, string $apMat,
        string $email, string $roleName
    ): void {
        $person = Person::firstOrCreate(
            ['document_type' => 'DNI', 'document_number' => $dni],
            [
                'code' => 'P-' . str_pad((string) (Person::max('id') + 1), 6, '0', STR_PAD_LEFT),
                'type' => 'individual',
                'names' => $nombres,
                'paternal_last_name' => $apPat,
                'maternal_last_name' => $apMat,
                'display_name' => trim("{$nombres} {$apPat} {$apMat}"),
                'legal_name' => trim("{$nombres} {$apPat} {$apMat}"),
                'email' => $email,
                'is_active' => true,
            ]
        );

        $employee = Employee::firstOrCreate(
            ['person_id' => $person->id],
            [
                'employee_code' => $this->nextEmployeeCode(),
                'hire_date' => now()->subMonths(6),
                'status' => 'active',
                'notes' => $label,
            ]
        );

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'person_id' => $person->id,
                'password' => Hash::make('password123'), // temporal, forzar cambio en producción
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole($roleName);
    }

    private function createDrillerWithoutAccess(string $dni, string $nombres, string $apPat, string $apMat): void
    {
        $person = Person::firstOrCreate(
            ['document_type' => 'DNI', 'document_number' => $dni],
            [
                'code' => 'P-' . str_pad((string) (Person::max('id') + 1), 6, '0', STR_PAD_LEFT),
                'type' => 'individual',
                'names' => $nombres,
                'paternal_last_name' => $apPat,
                'maternal_last_name' => $apMat,
                'display_name' => trim("{$nombres} {$apPat} {$apMat}"),
                'legal_name' => trim("{$nombres} {$apPat} {$apMat}"),
                'is_active' => true,
            ]
        );

        Employee::firstOrCreate(
            ['person_id' => $person->id],
            [
                'employee_code' => $this->nextEmployeeCode(),
                'hire_date' => now()->subMonths(3),
                'status' => 'active',
                'notes' => 'Perforista',
            ]
        );

        // Nota: NO se crea User aquí — el perforista no tiene acceso al sistema,
        // consistente con lo que definiste desde el inicio del diseño.
    }
}