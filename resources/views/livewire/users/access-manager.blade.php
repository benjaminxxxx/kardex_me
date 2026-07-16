<flux:modal wire:model.self="show" class="md:w-[480px]">
    <div class="space-y-6">
        <flux:heading size="lg">Acceso al sistema</flux:heading>
        <flux:text class="text-zinc-500">{{ $personDisplayName }}</flux:text>

        <input type="text" name="fake_username" style="display:none" tabindex="-1" autocomplete="off">
        <input type="password" name="fake_password" style="display:none" tabindex="-1" autocomplete="off">

        @if ($hasAccount)

            @if ($confirmingRemoval)
                {{-- ===== Confirmación de quitar acceso ===== --}}
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.heading>¿Quitar acceso al sistema?</flux:callout.heading>
                    <flux:callout.text>
                        Esto eliminará la cuenta de acceso de <strong>{{ $personDisplayName }}</strong> ({{ $email }})
                        y su rol asignado. No podrá iniciar sesión hasta que se le cree una cuenta nueva.
                    </flux:callout.text>
                </flux:callout>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="cancelRemoveAccess">Cancelar</flux:button>
                    <flux:button variant="danger" wire:click="removeAccess" icon="trash">
                        Sí, quitar acceso
                    </flux:button>
                </div>

            @else
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.text>Cuenta activa: {{ $email }}</flux:callout.text>
                </flux:callout>

                <flux:input.group>
                    <flux:input wire:model="email" type="email" autocomplete="off" />
                    <flux:button icon="check" wire:click="updateEmail">Actualizar correo</flux:button>
                </flux:input.group>

                {{-- ===== Rol asignado ===== --}}
                <flux:field>
                    <flux:label>Rol asignado</flux:label>
                    <div class="flex gap-2">
                        <flux:select wire:model="selectedRole" placeholder="Sin rol asignado" class="flex-1">
                            @foreach ($availableRoles as $role)
                                <flux:select.option value="{{ $role }}">{{ $role }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button icon="check" wire:click="updateRole">Guardar</flux:button>
                    </div>
                    <flux:error name="selectedRole" />
                    @if (!$selectedRole)
                        <flux:text size="sm" class="text-amber-600">
                            Sin rol, esta cuenta no podrá acceder a ningún módulo del sistema.
                        </flux:text>
                    @endif
                </flux:field>

                <flux:separator />

                @if (!$showPasswordForm)
                    <flux:button variant="ghost" icon="key" wire:click="togglePasswordForm">
                        Cambiar contraseña
                    </flux:button>
                @else
                    <flux:input wire:model="password" type="password" label="Nueva contraseña" autocomplete="new-password" />
                    <flux:input wire:model="password_confirmation" type="password" label="Confirmar nueva contraseña"
                        autocomplete="new-password" />

                    <div class="flex gap-2">
                        <flux:button variant="ghost" wire:click="togglePasswordForm">Cancelar</flux:button>
                        <flux:button variant="primary" wire:click="updatePassword" icon="check">Guardar nueva contraseña
                        </flux:button>
                    </div>
                @endif

                {{-- 👇 La zona de peligro solo se muestra cuando NO hay una edición en curso --}}
                @unless ($showPasswordForm)
                    <flux:separator />

                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                        <flux:text class="font-medium text-red-700 dark:text-red-400">Zona de peligro</flux:text>
                        <flux:text size="sm" class="mt-1 text-red-600 dark:text-red-400">
                            Al quitar el acceso, la persona ya no podrá iniciar sesión en el sistema.
                        </flux:text>
                        <flux:button variant="danger" size="sm" icon="trash" class="mt-3" wire:click="askRemoveAccess">
                            Quitar acceso
                        </flux:button>
                    </div>
                @endunless
            @endif

        @else
            <flux:callout variant="secondary" icon="information-circle">
                <flux:callout.text>Esta persona todavía no tiene cuenta de acceso.</flux:callout.text>
            </flux:callout>

            <flux:input wire:model="email" type="email" label="Correo de acceso" autocomplete="off" />
            <flux:input wire:model="password" type="password" label="Contraseña" autocomplete="new-password" />
            <flux:input wire:model="password_confirmation" type="password" label="Confirmar contraseña"
                autocomplete="new-password" />

            <flux:select wire:model="selectedRole" label="Rol" placeholder="Seleccionar rol...">
                @foreach ($availableRoles as $role)
                    <flux:select.option value="{{ $role }}">{{ $role }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="selectedRole" />

            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="createAccount" icon="key">Crear acceso</flux:button>
            </div>
        @endif
    </div>
</flux:modal>