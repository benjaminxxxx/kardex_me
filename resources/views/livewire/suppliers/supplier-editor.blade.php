<flux:modal wire:model.self="show" class="md:w-[720px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Editar proveedor</flux:heading>
            <flux:text class="mt-1 text-zinc-500">{{ $displayName }}</flux:text>
        </div>

        <flux:tab.group :selected="$activeTab" wire:key="supplier-editor-{{ $supplierId }}-{{ $activeTab }}">
            <flux:tabs variant="segmented">
                <flux:tab name="general">Datos generales</flux:tab>
                <flux:tab name="branches">Direcciones</flux:tab>
                <flux:tab name="bank-accounts">Métodos de pago</flux:tab>
            </flux:tabs>

            {{-- ===== Tab: General / Homologación ===== --}}
            <flux:tab.panel name="general">
                <div class="space-y-4">
                    <flux:select wire:model="status" label="Estado de homologación">
                        <flux:select.option value="prospect">Prospecto</flux:select.option>
                        <flux:select.option value="approved">Homologado</flux:select.option>
                        <flux:select.option value="suspended">Suspendido</flux:select.option>
                        <flux:select.option value="blacklisted">Vetado</flux:select.option>
                    </flux:select>

                    <flux:textarea wire:model="notes" label="Observaciones" />
                </div>
            </flux:tab.panel>

            {{-- ===== Tab: Direcciones ===== --}}
            <flux:tab.panel name="branches">
                <div class="space-y-4">
                    @foreach ($branches as $index => $branch)
                        <flux:card class="space-y-4 bg-zinc-50 dark:bg-zinc-900">
                            <div class="flex items-center justify-between">
                                <flux:select wire:model="branches.{{ $index }}.type" label="Tipo" class="max-w-xs">
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
                                <flux:input wire:model="branches.{{ $index }}.name" label="Nombre de referencia" class="md:col-span-2" />
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
                </div>
            </flux:tab.panel>

            {{-- ===== Tab: Cuentas bancarias ===== --}}
            <flux:tab.panel name="bank-accounts">
                <div class="space-y-4">
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
                                    <flux:input wire:model="bankAccounts.{{ $index }}.bank_name" label="Entidad bancaria" />
                                    <flux:select wire:model="bankAccounts.{{ $index }}.currency" label="Moneda">
                                        <flux:select.option value="PEN">Soles</flux:select.option>
                                        <flux:select.option value="USD">Dólares</flux:select.option>
                                    </flux:select>
                                    <flux:input wire:model="bankAccounts.{{ $index }}.account_number" label="N° de cuenta" />
                                    <flux:input wire:model="bankAccounts.{{ $index }}.cci" label="CCI" />
                                </div>
                            @else
                                <div class="grid gap-4 md:grid-cols-2">
                                    <flux:select wire:model="bankAccounts.{{ $index }}.wallet_provider" label="Proveedor">
                                        <flux:select.option value="">Seleccionar proveedor</flux:select.option>
                                        <flux:select.option value="Yape">Yape</flux:select.option>
                                        <flux:select.option value="Bim">Bim</flux:select.option>
                                        <flux:select.option value="Plin">Plin</flux:select.option>
                                    </flux:select>
                                    <flux:input wire:model="bankAccounts.{{ $index }}.wallet_phone" label="Celular vinculado" />
                                </div>
                            @endif

                            <flux:input wire:model="bankAccounts.{{ $index }}.account_holder_name" label="Titular" />
                            <flux:checkbox wire:model="bankAccounts.{{ $index }}.is_main" label="Cuenta principal" />
                        </flux:card>
                    @endforeach

                    <flux:button variant="ghost" icon="plus" wire:click="addBankAccount">
                        Agregar otro método de pago
                    </flux:button>
                </div>
            </flux:tab.panel>
        </flux:tab.group>

        <div class="flex justify-between">
            <flux:button variant="ghost" wire:click="close">Cancelar</flux:button>
            <flux:button variant="primary" wire:click="save" icon="check">Guardar cambios</flux:button>
        </div>
    </div>
</flux:modal>