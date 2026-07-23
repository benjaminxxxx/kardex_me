<flux:modal wire:model.self="show" flyout variant="floating" class="md:w-lg">
    @if ($supplier)
        @php
            $persona = $supplier->person;
            $esEmpresa = $persona->type === 'company';

            $estadoColors = ['prospect' => 'zinc', 'approved' => 'green', 'suspended' => 'yellow', 'blacklisted' => 'red'];
            $estadoLabels = ['prospect' => 'Prospecto', 'approved' => 'Homologado', 'suspended' => 'Suspendido', 'blacklisted' => 'Vetado'];

            $tipoDireccionLabels = ['fiscal' => 'Fiscal', 'laboratory' => 'Laboratorio', 'field' => 'Campo', 'warehouse' => 'Almacén', 'other' => 'Otro'];
        @endphp

        <div class="space-y-6">

            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="lg">{{ $persona->display_name }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-500">Código: {{ $supplier->supplier_code }}</flux:text>
                </div>
                <flux:badge size="sm" :color="$esEmpresa ? 'blue' : 'zinc'">
                    {{ $esEmpresa ? 'Empresa' : 'Persona Natural' }}
                </flux:badge>
            </div>

            {{-- ===================== DATOS DE PERSONA / EMPRESA ===================== --}}
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

                    @if ($persona->notes)
                        <div class="md:col-span-2">
                            <flux:text size="sm" class="text-zinc-500">Notas</flux:text>
                            <flux:text class="font-medium">{{ $persona->notes }}</flux:text>
                        </div>
                    @endif
                </div>

                <x-audit-trail :model="$persona" />
            </flux:card>

            {{-- ===================== HOMOLOGACIÓN ===================== --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide">
                    Homologación
                </flux:heading>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:text size="sm" class="text-zinc-500">Estado</flux:text>
                        <div>
                            <flux:badge size="sm" :color="$estadoColors[$supplier->status]">
                                {{ $estadoLabels[$supplier->status] }}
                            </flux:badge>
                        </div>
                    </div>

                    @if ($supplier->notes)
                        <div class="md:col-span-2">
                            <flux:text size="sm" class="text-zinc-500">Observaciones</flux:text>
                            <flux:text class="font-medium">{{ $supplier->notes }}</flux:text>
                        </div>
                    @endif
                </div>

                <x-audit-trail :model="$supplier" />
            </flux:card>

            {{-- ===================== DIRECCIONES ===================== --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide">
                    Direcciones
                </flux:heading>

                @forelse ($supplier->branches as $branch)
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <flux:text class="font-medium">{{ $branch->name }}</flux:text>
                            <div class="flex items-center gap-2">
                                @if ($branch->is_main)
                                    <flux:badge size="sm" color="blue">Principal</flux:badge>
                                @endif
                                <flux:badge size="sm" color="zinc">
                                    {{ $tipoDireccionLabels[$branch->type] ?? $branch->type }}
                                </flux:badge>
                            </div>
                        </div>
                        <flux:text size="sm" class="text-zinc-500 mt-1">
                            {{ $branch->address ?: '—' }}
                            @if($branch->city || $branch->state)
                                <span class="text-zinc-400">
                                    ({{ collect([$branch->city, $branch->state])->filter()->implode(', ') }})
                                </span>
                            @endif
                        </flux:text>
                        @if ($branch->phone || $branch->email)
                            <flux:text size="sm" class="text-zinc-500">
                                {{ collect([$branch->phone, $branch->email])->filter()->implode(' · ') }}
                            </flux:text>
                        @endif
                    </div>
                @empty
                    <flux:text class="text-zinc-500">Sin direcciones registradas.</flux:text>
                @endforelse
            </flux:card>

            {{-- ===================== MÉTODOS DE PAGO ===================== --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide">
                    Métodos de pago
                </flux:heading>

                @forelse ($supplier->bankAccounts as $account)
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            @if ($account->type === 'bank_account')
                                <flux:text class="font-medium">{{ $account->bank_name }}</flux:text>
                            @else
                                <flux:text class="font-medium">{{ $account->wallet_provider }}</flux:text>
                            @endif
                            <div class="flex items-center gap-2">
                                @if ($account->is_main)
                                    <flux:badge size="sm" color="blue">Principal</flux:badge>
                                @endif
                                <flux:badge size="sm" color="zinc">
                                    {{ $account->type === 'bank_account' ? 'Cuenta bancaria' : 'Billetera digital' }}
                                </flux:badge>
                            </div>
                        </div>

                        @if ($account->type === 'bank_account')
                            <flux:text size="sm" class="text-zinc-500 mt-1">
                                Cuenta: {{ $account->account_number ?: '—' }} · CCI: {{ $account->cci ?: '—' }} · {{ $account->currency }}
                            </flux:text>
                        @else
                            <flux:text size="sm" class="text-zinc-500 mt-1">
                                Celular: {{ $account->wallet_phone ?: '—' }}
                            </flux:text>
                        @endif

                        @if ($account->account_holder_name)
                            <flux:text size="sm" class="text-zinc-500">
                                Titular: {{ $account->account_holder_name }}
                            </flux:text>
                        @endif
                    </div>
                @empty
                    <flux:text class="text-zinc-500">Sin métodos de pago registrados.</flux:text>
                @endforelse
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
                    </div>

                    <x-audit-trail :model="$persona->user" />
                @else
                    <flux:callout variant="secondary" icon="information-circle">
                        <flux:callout.text>Este proveedor no tiene cuenta de acceso al sistema.</flux:callout.text>
                    </flux:callout>
                @endif
            </flux:card>

            <div class="flex justify-end">
                <flux:button variant="ghost" wire:click="close">Cerrar</flux:button>
            </div>
        </div>
    @endif
</flux:modal>