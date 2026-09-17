<flux:modal wire:model.self="show" class="w-full max-w-full md:max-w-lg lg:max-w-[720px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ $mode === 'edit' ? 'Editar información' : 'Registrar nueva persona o empresa' }}
            </flux:heading>
            <flux:text class="mt-1">
                {{ $mode === 'edit' ? 'Actualiza los datos registrados.' : 'Verifica bien el documento antes de guardar.' }}
            </flux:text>
        </div>

        {{-- ===== Selector de tipo ===== --}}
        <flux:radio.group wire:model.live="type" variant="segmented" class="w-full">
            <flux:radio value="individual" label="Persona Natural" class="flex-1" />
            <flux:radio value="company" label="Empresa" class="flex-1" />
        </flux:radio.group>

        <div class="grid gap-4 md:grid-cols-3">
            {{-- ===== Identidad ===== --}}
            <flux:select wire:model="documentType" label="Tipo de documento">
                @if ($type === 'company')
                    <flux:select.option value="RUC">RUC</flux:select.option>
                @else
                    <flux:select.option value="DNI">DNI</flux:select.option>
                    <flux:select.option value="CE">Carné de Extranjería</flux:select.option>
                    <flux:select.option value="PASAPORTE">Pasaporte</flux:select.option>
                @endif
            </flux:select>
            <flux:input wire:model="documentNumber" label="N° de documento" class="md:col-span-2" />

            @if ($type === 'individual')
                {{-- ===== Solo Persona Natural ===== --}}
                <flux:input wire:model="names" label="Nombres" class="md:col-span-3" />
                <flux:input wire:model="paternalLastName" label="Apellido paterno" />
                <flux:input wire:model="maternalLastName" label="Apellido materno" />

                <flux:input wire:model="birthDate" type="date" label="Fecha de nacimiento" />
                <flux:select wire:model="gender" label="Género" placeholder="Seleccionar...">
                    <flux:select.option value="male">Masculino</flux:select.option>
                    <flux:select.option value="female">Femenino</flux:select.option>
                    <flux:select.option value="other">Otro</flux:select.option>
                </flux:select>

                <flux:select wire:model="maritalStatus" label="Estado civil" placeholder="Seleccionar...">
                    <flux:select.option value="single">Soltero(a)</flux:select.option>
                    <flux:select.option value="married">Casado(a)</flux:select.option>
                    <flux:select.option value="divorced">Divorciado(a)</flux:select.option>
                    <flux:select.option value="widowed">Viudo(a)</flux:select.option>
                </flux:select>
            @else
                {{-- ===== Solo Empresa ===== --}}
                <flux:input wire:model="companyName" label="Nombre comercial" />
                <flux:input wire:model="legalName" label="Razón social" class="md:col-span-2" />
            @endif

            {{-- ===== Contacto ===== --}}
            <flux:input wire:model="mobile" label="Celular" />
            <flux:input wire:model="phone" label="Teléfono fijo" />
            <flux:input wire:model="email" type="email" label="Correo" autocomplete="off" />

            {{-- ===== Dirección ===== --}}
            <flux:input wire:model="country" label="País" />
            <flux:input wire:model="state" label="Departamento / Estado" />
            <flux:input wire:model="city" label="Provincia / Ciudad" />
            <flux:input wire:model="district" label="Distrito" />
            <flux:input wire:model="postalCode" label="Código postal" />
            <flux:input wire:model="address" label="Dirección" class="md:col-span-3" />

            {{-- ===== Notas ===== --}}
            <div class="col-span-3">
                <flux:textarea wire:model="notes" label="Notas"/>
            </div>
        </div>

        <div class="flex justify-between">
            <flux:button variant="ghost" wire:click="cancel" icon="arrow-left">
                {{ $mode === 'edit' ? 'Cancelar' : 'Volver a buscar' }}
            </flux:button>
            <flux:button variant="primary" wire:click="save" icon="check">
                {{ $mode === 'edit' ? 'Guardar cambios' : 'Guardar' }}
            </flux:button>
        </div>
    </div>
</flux:modal>