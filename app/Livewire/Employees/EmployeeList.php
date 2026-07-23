<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use App\Models\Person;
use App\Support\ExcelHelper;
use Exception;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

#[Title('Empleados')]
class EmployeeList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $access = ''; // '' | 'with_user' | 'without_user'
    public string $sortBy = 'employee_code';
    public string $sortDirection = 'desc';
    #[On('person-edited')]

    public function onPersonEdited()
    {
        $this->resetPage();
    }
    #[On('employee-edited')]

    public function onEmployeeEdited()
    {
        $this->resetPage();
    }
    #[On('user-created')]
    public function onUserCreated()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatingStatus()
    {
        $this->resetPage();
    }
    public function updatingAccess()
    {
        $this->resetPage();
    }

    public function sort($column)
    {
        $this->sortDirection = $this->sortBy === $column
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->sortBy = $column;
    }

    public function manageAccess(int $personId): void
    {
        $this->dispatch('open-access-manager', personId: $personId);
    }

    public function editPersonalInfo(int $personId): void
    {
        $this->dispatch('open-person-editor', personId: $personId);
    }

    public function editEmploymentInfo(int $employeeId): void
    {
        $this->dispatch('open-employee-editor', employeeId: $employeeId);
    }

    public function getEmployeesProperty()
    {
        return Employee::query()
            ->with(['person.user']) // 👈 clave: sin esto, cada fila dispara N+1 y además nada aparece
            ->when($this->search, function ($query) {
                $term = $this->search;
                $query->where(function ($q) use ($term) {
                    $q->where('employee_code', 'like', "%{$term}%")
                        ->orWhereHas('person', function ($q) use ($term) {
                            $q->where('display_name', 'like', "%{$term}%")
                                ->orWhere('document_number', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%")
                                ->orWhere('mobile', 'like', "%{$term}%");
                        });
                });
            })
            ->when($this->status !== '', fn($q) => $q->where('status', $this->status))
            ->when($this->access === 'with_user', fn($q) => $q->whereHas('person.user'))
            ->when($this->access === 'without_user', fn($q) => $q->whereDoesntHave('person.user'))
            ->orderBy(
                in_array($this->sortBy, ['employee_code', 'hire_date', 'status']) ? $this->sortBy : 'employee_code',
                $this->sortDirection
            )
            ->paginate(15);
    }
    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'access']);
    }
    public function viewDetails(int $employeeId): void
    {
        $this->dispatch('open-employee-details', employeeId: $employeeId);
    }
    public function exportToExcel()
    {
        try {
            $spreadsheet = ExcelHelper::loadTemplate('rpt_lista_empleados.xlsx');
            $hoja = $spreadsheet->getSheetByName('EMPLEADO');

            if (!$hoja) {
                throw new Exception("La plantilla no contiene la hoja 'EMPLEADO'.");
            }

            $filaInicio = 5;
            $filaActual = $filaInicio;

            $tipoPersonaLabels = [
                'individual' => 'Persona Natural',
                'company' => 'Persona Jurídica',
            ];

            $generoLabels = [
                'male' => 'Masculino',
                'female' => 'Femenino',
                'other' => 'Otro',
            ];

            $estadoCivilLabels = [
                'single' => 'Soltero(a)',
                'married' => 'Casado(a)',
                'divorced' => 'Divorciado(a)',
                'widowed' => 'Viudo(a)',
            ];

            $estadoEmpleadoLabels = [
                'active' => 'Activo',
                'inactive' => 'Inactivo',
                'suspended' => 'Suspendido',
                'terminated' => 'Cesado',
            ];

            // Recorremos TODAS las personas, sin ningún filtro de la tabla principal.
            // chunkById evita cargar todo en memoria si la tabla crece mucho.
            Person::query()
                ->with([
                    'employee',
                    'employee.createdBy',
                    'employee.updatedBy',
                    'user',
                    'user.roles',
                    'user.createdBy',
                    'user.updatedBy',
                    'createdBy',
                    'updatedBy',
                ])
                ->orderBy('id')
                ->chunkById(200, function ($personas) use ($hoja, &$filaActual, $tipoPersonaLabels, $generoLabels, $estadoCivilLabels, $estadoEmpleadoLabels) {

                    foreach ($personas as $index => $persona) {

                        $empleado = $persona->employee;
                        $usuario = $persona->user;

                        // ============ N° ============
                        $hoja->setCellValue("A{$filaActual}", $filaActual - 4); // correlativo empezando en 1
    
                        // ============ DATOS PERSONALES ============
                        $hoja->setCellValue("B{$filaActual}", $persona->code);
                        $hoja->setCellValue("C{$filaActual}", $tipoPersonaLabels[$persona->type] ?? $persona->type);
                        $hoja->setCellValue("D{$filaActual}", $persona->document_type);
                        $hoja->setCellValue("E{$filaActual}", $persona->document_number);
                        $hoja->setCellValue("F{$filaActual}", $persona->company_name ?? '');
                        $hoja->setCellValue("G{$filaActual}", $persona->legal_name ?? '');
                        $hoja->setCellValue("H{$filaActual}", $persona->names ?? '');
                        $hoja->setCellValue("I{$filaActual}", $persona->paternal_last_name ?? '');
                        $hoja->setCellValue("J{$filaActual}", $persona->maternal_last_name ?? '');

                        $this->setFechaCell($hoja, "K{$filaActual}", $persona->birth_date);

                        $hoja->setCellValue("L{$filaActual}", $generoLabels[$persona->gender] ?? '');
                        $hoja->setCellValue("M{$filaActual}", $estadoCivilLabels[$persona->marital_status] ?? '');
                        $hoja->setCellValue("N{$filaActual}", $persona->mobile ?? '');
                        $hoja->setCellValue("O{$filaActual}", $persona->phone ?? '');
                        $hoja->setCellValue("P{$filaActual}", $persona->email ?? '');
                        $hoja->setCellValue("Q{$filaActual}", $persona->country ?? '');
                        $hoja->setCellValue("R{$filaActual}", $persona->state ?? '');
                        $hoja->setCellValue("S{$filaActual}", $persona->city ?? '');
                        $hoja->setCellValue("T{$filaActual}", $persona->district ?? '');
                        $hoja->setCellValue("U{$filaActual}", $persona->postal_code ?? '');
                        $hoja->setCellValue("V{$filaActual}", $persona->address ?? '');
                        $hoja->setCellValue("W{$filaActual}", $persona->notes ?? '');
                        $hoja->setCellValue("X{$filaActual}", $persona->is_active ? 'Activo' : 'Inactivo');

                        $this->setFechaCell($hoja, "Y{$filaActual}", $persona->created_at, true);
                        $this->setFechaCell($hoja, "Z{$filaActual}", $persona->updated_at, true);

                        $hoja->setCellValue("AA{$filaActual}", $persona->created_by_name ?? '');
                        $hoja->setCellValue("AB{$filaActual}", $persona->updated_by_name ?? '');

                        // ============ DATOS DE ACCESO (opcional) ============
                        $hoja->setCellValue("AC{$filaActual}", $usuario->email ?? '');
                        $hoja->setCellValue("AD{$filaActual}", $usuario?->roles->first()?->name ?? '');

                        if ($usuario) {
                            $this->setFechaCell($hoja, "AE{$filaActual}", $usuario->created_at, true);
                            $this->setFechaCell($hoja, "AF{$filaActual}", $usuario->updated_at, true);
                        }

                        $hoja->setCellValue("AG{$filaActual}", $usuario->created_by_name ?? '');
                        $hoja->setCellValue("AH{$filaActual}", $usuario->updated_by_name ?? '');

                        // ============ DATOS DE EMPLEADO (opcional) ============
                        $hoja->setCellValue("AI{$filaActual}", $empleado->employee_code ?? '');

                        if ($empleado) {
                            $this->setFechaCell($hoja, "AJ{$filaActual}", $empleado->hire_date);
                            $this->setFechaCell($hoja, "AK{$filaActual}", $empleado->termination_date);
                        }

                        $hoja->setCellValue("AL{$filaActual}", $estadoEmpleadoLabels[$empleado->status ?? ''] ?? '');
                        $hoja->setCellValue("AM{$filaActual}", $empleado->notes ?? '');

                        $filaActual++;
                    }
                });

            $filaFin = max($filaActual - 1, $filaInicio);

            // Como la plantilla no tiene una "Tabla" de Excel, dibujamos los bordes manualmente
            $this->aplicarBordesRango($hoja, "A{$filaInicio}:AM{$filaFin}");

            return ExcelHelper::download($spreadsheet, 'REPORTE_EMPLEADOS.xlsx');

        } catch (\Throwable $th) {
            Flux::toast('Error al exportar a Excel: ' . $th->getMessage(), 'error');
        }
    }
    /**
     * Escribe una fecha como valor numérico de Excel y le aplica el formato visual dd-mm-yyyy.
     * Esto es clave: PhpSpreadsheet guarda fechas como número serial internamente;
     * el formato solo cambia cómo se VE, no el dato real, así que Excel sigue
     * permitiendo ordenar/filtrar/calcular con esas celdas como fechas reales.
     */
    private function setFechaCell($hoja, string $celda, $fecha, bool $conHora = false): void
    {
        if (!$fecha) {
            return;
        }

        $hoja->setCellValue($celda, ExcelDate::PHPToExcel($fecha));

        $hoja->getStyle($celda)
            ->getNumberFormat()
            ->setFormatCode($conHora ? 'dd-mm-yyyy hh:mm' : 'dd-mm-yyyy');
    }

    /**
     * Aplica bordes delgados a todo el rango de datos, ya que la plantilla
     * no cuenta con un objeto Table de Excel que los genere automáticamente.
     */
    private function aplicarBordesRango($hoja, string $rango): void
    {
        $hoja->getStyle($rango)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'B7B7B7'],
                ],
            ],
        ]);

        // Opcional: resalta el borde exterior más grueso para que se distinga
        // visualmente del resto de la hoja
        $hoja->getStyle($rango)->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '444444'],
                ],
            ],
        ]);
    }
    public function render()
    {
        return view('livewire.employees.employee-list');
    }
}