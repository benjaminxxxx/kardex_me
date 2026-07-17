<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <flux:heading>Eliminar cuenta</flux:heading>
        <flux:subheading>Elimine su cuenta y todos los datos asociados a ella.</flux:subheading>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
            Eliminar cuenta
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    ¿Está seguro de que desea eliminar su cuenta?
                </flux:heading>

                <flux:subheading>
                    Una vez eliminada su cuenta, todos los datos y recursos asociados serán eliminados de forma permanente. Para confirmar esta acción, ingrese su contraseña.
                </flux:subheading>
            </div>

            <flux:input
                wire:model="password"
                label="Contraseña"
                type="password"
                viewable
            />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">
                        Cancelar
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">
                    Eliminar cuenta
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>