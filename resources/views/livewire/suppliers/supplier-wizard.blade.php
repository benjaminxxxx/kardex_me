<div class="space-y-6">

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('suppliers.index') }}">Proveedores</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Nuevo proveedor</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Nuevo proveedor</flux:heading>

    {{-- ===== Indicador de pasos ===== --}}
    <div class="flex items-center gap-2">
        @foreach (['Persona', 'Homologación', 'Direcciones', 'Cuentas de pago'] as $i => $label)
            <div class="flex items-center gap-2">
                <flux:badge :color="($step === $i + 1) ? 'blue' : ($step > $i + 1 ? 'green' : 'zinc')" size="sm">
                    {{ $i + 1 }}
                </flux:badge>
                <flux:text size="sm" class="{{ $step === $i + 1 ? 'font-medium' : 'text-zinc-500' }}">
                    {{ $label }}
                </flux:text>
            </div>
            @if (!$loop->last)
                <flux:separator vertical class="h-4" />
            @endif
        @endforeach
    </div>

    {{-- ===== Paso 1: Persona ===== --}}
    @if ($step === 1)
        <flux:card class="space-y-4">
            <flux:text class="text-zinc-500">Esperando selección de persona o empresa...</flux:text>
            <flux:button variant="primary" wire:click="$dispatch('open-person-selector', { context: 'supplier-wizard' })">
                Abrir buscador
            </flux:button>
            @error('personId') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:card>
    @endif

    {{-- ===== Paso 2: Homologación ===== --}}
    @if ($step === 2)
        <flux:card class="space-y-6">
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.heading>{{ $personDisplayName }}</flux:callout.heading>
                <flux:callout.text>Documento: {{ $personDocumentNumber }}</flux:callout.text>
                <x-slot name="actions">
                    <flux:button size="sm" variant="ghost" wire:click="changePerson">Cambiar</flux:button>
                </x-slot>
            </flux:callout>

            <flux:select wire:model="status" label="Estado de homologación">
                <flux:select.option value="prospect">Prospecto</flux:select.option>
                <flux:select.option value="approved">Homologado</flux:select.option>
                <flux:select.option value="suspended">Suspendido</flux:select.option>
                <flux:select.option value="blacklisted">Vetado</flux:select.option>
            </flux:select>

            <flux:textarea wire:model="notes" label="Observaciones" />

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="prevStep" icon="arrow-left">Atrás</flux:button>
                <flux:button variant="primary" wire:click="nextStep" icon="arrow-right">Continuar</flux:button>
            </div>
        </flux:card>
    @endif

    {{-- ===== Paso 3: Direcciones ===== --}}
    @if ($step === 3)
        <flux:card class="space-y-6">
            <flux:heading size="sm">Direcciones</flux:heading>
            <flux:text class="text-zinc-500 text-sm">
                Agrega la dirección fiscal (obligatoria) y otras como laboratorio o campo, si aplica.
            </flux:text>

            @foreach ($branches as $index => $branch)
                <flux:card class="space-y-4 bg-zinc-50 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <flux:select wire:model="branches.{{ $index }}.type" label="Tipo de dirección" class="max-w-xs">
                            <flux:select.option value="fiscal">Fiscal</flux:select.option>
                            <flux:select.option value="laboratory">Laboratorio</flux:select.option>
                            <flux:select.option value="field">Campo</flux:select.option>
                            <flux:select.option value="warehouse">Almacén</flux:select.option>
                            <flux:select.option value="other">Otro</flux:select.option>
                        </flux:select>

                        @if (count($branches) > 1)
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeBranch({{ $index }})" />
                        @endif
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="branches.{{ $index }}.name" label="Nombre de referencia"
                            placeholder="Ej: Sede principal, Laboratorio Arequipa" class="md:col-span-2" />

                        <flux:input wire:model="branches.{{ $index }}.state" label="Departamento" />
                        <flux:input wire:model="branches.{{ $index }}.city" label="Ciudad" />

                        <flux:input wire:model="branches.{{ $index }}.address" label="Dirección" class="md:col-span-2" />

                        <flux:input wire:model="branches.{{ $index }}.phone" label="Teléfono" />
                        <flux:input wire:model="branches.{{ $index }}.email" label="Correo" />
                    </div>

                    <flux:checkbox wire:model="branches.{{ $index }}.is_main" label="Dirección principal" />
                </flux:card>
            @endforeach

            <flux:button variant="ghost" icon="plus" wire:click="addBranch">
                Agregar otra dirección
            </flux:button>

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="prevStep" icon="arrow-left">Atrás</flux:button>
                <flux:button variant="primary" wire:click="nextStep" icon="arrow-right">Continuar</flux:button>
            </div>
        </flux:card>
    @endif

    {{-- ===== Paso 4: Cuentas bancarias ===== --}}
    @if ($step === 4)
        <flux:card class="space-y-6">
            <flux:heading size="sm">Métodos de pago</flux:heading>
            <flux:text class="text-zinc-500 text-sm">
                Registra la cuenta principal y billeteras digitales aceptadas.
            </flux:text>

            @foreach ($bankAccounts as $index => $account)
                <flux:card class="space-y-4 bg-zinc-50 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <flux:select wire:model.live="bankAccounts.{{ $index }}.type" label="Tipo" class="max-w-xs">
                            <flux:select.option value="bank_account">Cuenta bancaria</flux:select.option>
                            <flux:select.option value="digital_wallet">Billetera digital</flux:select.option>
                        </flux:select>

                        @if (count($bankAccounts) > 1)
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeBankAccount({{ $index }})" />
                        @endif
                    </div>

                    @if ($account['type'] === 'bank_account')
                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:input wire:model="bankAccounts.{{ $index }}.bank_name" label="Entidad bancaria"
                                placeholder="BCP, BBVA, Interbank..." />
                            <flux:select wire:model="bankAccounts.{{ $index }}.currency" label="Moneda">
                                <flux:select.option value="PEN">Soles</flux:select.option>
                                <flux:select.option value="USD">Dólares</flux:select.option>
                            </flux:select>
                            <flux:input wire:model="bankAccounts.{{ $index }}.account_number" label="N° de cuenta" />
                            <flux:input wire:model="bankAccounts.{{ $index }}.cci" label="Código interbancario (CCI)" />
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:select wire:model="bankAccounts.{{ $index }}.wallet_provider" label="Proveedor">
                                <flux:select.option value="Yape">Yape</flux:select.option>
                                <flux:select.option value="Bim">Bim</flux:select.option>
                                <flux:select.option value="Plin">Plin</flux:select.option>
                            </flux:select>
                            <flux:input wire:model="bankAccounts.{{ $index }}.wallet_phone" label="Celular vinculado" />
                        </div>
                    @endif

                    <flux:input wire:model="bankAccounts.{{ $index }}.account_holder_name" label="Titular" />
                    <flux:checkbox wire:model="bankAccounts.{{ $index }}.is_main" label="Cuenta principal para pagos" />
                </flux:card>
            @endforeach

            <flux:button variant="ghost" icon="plus" wire:click="addBankAccount">
                Agregar otro método de pago
            </flux:button>

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="prevStep" icon="arrow-left">Atrás</flux:button>
                <flux:button variant="primary" wire:click="save" icon="check">
                    Guardar proveedor
                </flux:button>
            </div>
        </flux:card>
    @endif

    {{-- Submódulos reutilizados --}}
    <livewire:person.person-selector />
    <livewire:person.person-registrar />
</div>