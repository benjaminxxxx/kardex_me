<flux:modal wire:model.self="show" flyout variant="floating" class="md:w-lg">
    @if ($employee)
        @php
            $persona = $employee->person;
            $esEmpresa = $persona->type === 'company';

            $generoLabels = ['male' => 'Masculino', 'female' => 'Femenino', 'other' => 'Otro'];
            $estadoCivilLabels = ['single' => 'Soltero(a)', 'married' => 'Casado(a)', 'divorced' => 'Divorciado(a)', 'widowed' => 'Viudo(a)'];
            $estadoEmpleadoColors = ['active' => 'green', 'inactive' => 'zinc', 'suspended' => 'yellow', 'terminated' => 'red'];
            $estadoEmpleadoLabels = ['active' => 'Activo', 'inactive' => 'Inactivo', 'suspended' => 'Suspendido', 'terminated' => 'Cesado'];
        @endphp

        <div class="space-y-6">

            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="lg">{{ $persona->display_name }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-500">Código: {{ $employee->employee_code }}</flux:text>
                </div>
                <flux:badge size="sm" :color="$esEmpresa ? 'blue' : 'zinc'">
                    {{ $esEmpresa ? 'Empresa' : 'Persona Natural' }}
                </flux:badge>
            </div>

            {{-- ===================== DATOS PERSONALES ===================== --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide">
                    {{ $esEmpresa ? 'Información de la empresa' : 'Información personal' }}
                </flux:heading>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:text size="sm" class="text-zinc-500">Documento</flux:text>
                        <flux:text class="font-medium">
                            {{ $persona->document_type }}: {{ $persona->document_number }}
                        </flux:text>
                    </div>

                    @if ($esEmpresa)
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Nombre comercial</flux:text>
                            <flux:text class="font-medium">{{ $persona->company_name ?: '—' }}</flux:text>
                        </div>

                        <div class="md:col-span-2">
                            <flux:text size="sm" class="text-zinc-500">Razón social</flux:text>
                            <flux:text class="font-medium">{{ $persona->legal_name ?: '—' }}</flux:text>
                        </div>
                    @else
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Fecha de nacimiento</flux:text>
                            <flux:text class="font-medium">
                                {{ $persona->birth_date?->format('d/m/Y') ?? '—' }}
                            </flux:text>
                        </div>

                        <div>
                            <flux:text size="sm" class="text-zinc-500">Género</flux:text>
                            <flux:text class="font-medium">{{ $generoLabels[$persona->gender] ?? '—' }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="sm" class="text-zinc-500">Estado civil</flux:text>
                            <flux:text class="font-medium">{{ $estadoCivilLabels[$persona->marital_status] ?? '—' }}</flux:text>
                        </div>
                    @endif

                    <div>
                        <flux:text size="sm" class="text-zinc-500">Celular</flux:text>
                        <flux:text class="font-medium">{{ $persona->mobile ?: '—' }}</flux:text>
                    </div>

                    @if ($persona->phone)
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Teléfono fijo</flux:text>
                            <flux:text class="font-medium">{{ $persona->phone }}</flux:text>
                        </div>
                    @endif

                    <div>
                        <flux:text size="sm" class="text-zinc-500">Correo</flux:text>
                        <flux:text class="font-medium">{{ $persona->email ?: '—' }}</flux:text>
                    </div>

                    <div class="md:col-span-2">
                        <flux:text size="sm" class="text-zinc-500">Dirección</flux:text>
                        <flux:text class="font-medium">
                            {{ $persona->address ?: '—' }}
                            @if($persona->district || $persona->city)
                                <span class="text-zinc-400">
                                    ({{ collect([$persona->district, $persona->city, $persona->state])->filter()->implode(', ') }})
                                </span>
                            @endif
                        </flux:text>
                    </div>

                    @if ($persona->notes)
                        <div class="md:col-span-2">
                            <flux:text size="sm" class="text-zinc-500">Notas</flux:text>
                            <flux:text class="font-medium">{{ $persona->notes }}</flux:text>
                        </div>
                    @endif
                </div>

                <x-audit-trail :model="$persona" />
            </flux:card>

            {{-- ===================== DATOS LABORALES ===================== --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide">
                    Información laboral
                </flux:heading>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:text size="sm" class="text-zinc-500">Fecha de ingreso</flux:text>
                        <flux:text class="font-medium">{{ $employee->hire_date->format('d/m/Y') }}</flux:text>
                    </div>

                    <div>
                        <flux:text size="sm" class="text-zinc-500">Fecha de cese</flux:text>
                        <flux:text class="font-medium">
                            {{ $employee->termination_date?->format('d/m/Y') ?? '—' }}
                        </flux:text>
                    </div>

                    <div>
                        <flux:text size="sm" class="text-zinc-500">Estado</flux:text>
                        <div>
                            <flux:badge size="sm" :color="$estadoEmpleadoColors[$employee->status]">
                                {{ $estadoEmpleadoLabels[$employee->status] }}
                            </flux:badge>
                        </div>
                    </div>

                    @if ($employee->notes)
                        <div class="md:col-span-2">
                            <flux:text size="sm" class="text-zinc-500">Observaciones</flux:text>
                            <flux:text class="font-medium">{{ $employee->notes }}</flux:text>
                        </div>
                    @endif
                </div>

                <x-audit-trail :model="$employee" />
            </flux:card>

            {{-- ===================== ACCESO AL SISTEMA ===================== --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide">
                    Acceso al sistema
                </flux:heading>

                @if ($persona->user)
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Correo de acceso</flux:text>
                            <flux:text class="font-medium">{{ $persona->user->email }}</flux:text>
                        </div>

                        <div>
                            <flux:text size="sm" class="text-zinc-500">Rol asignado</flux:text>
                            <div>
                                @if ($persona->user->roles->first())
                                    <flux:badge size="sm" color="blue">
                                        {{ $persona->user->roles->first()->name }}
                                    </flux:badge>
                                @else
                                    <flux:badge size="sm" color="amber">Sin rol</flux:badge>
                                @endif
                            </div>
                        </div>

                        <div>
                            <flux:text size="sm" class="text-zinc-500">Verificado</flux:text>
                            <div>
                                @if ($persona->user->email_verified_at)
                                    <flux:badge size="sm" color="green">
                                        {{ $persona->user->email_verified_at->format('d/m/Y') }}
                                    </flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">No verificado</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>

                    <x-audit-trail :model="$persona->user" />
                @else
                    <flux:callout variant="secondary" icon="information-circle">
                        <flux:callout.text>Esta persona no tiene cuenta de acceso al sistema.</flux:callout.text>
                    </flux:callout>
                @endif
            </flux:card>

            <div class="flex justify-end">
                <flux:button variant="ghost" wire:click="close">Cerrar</flux:button>
            </div>
        </div>
    @endif
</flux:modal>