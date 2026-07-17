<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">Configuración del perfil</flux:heading>

    <x-settings.layout
        heading="Perfil"
        subheading="Actualice su nombre y dirección de correo electrónico"
    >
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input
                wire:model="name"
                label="Nombre"
                type="text"
                required
                autofocus
                autocomplete="name"
            />

            <div>
                <flux:input
                    wire:model="email"
                    label="Correo electrónico"
                    type="email"
                    required
                    autocomplete="email"
                />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            Su dirección de correo electrónico aún no ha sido verificada.

                            <flux:link
                                class="text-sm cursor-pointer"
                                wire:click.prevent="resendVerificationNotification"
                            >
                                Haga clic aquí para reenviar el correo de verificación.
                            </flux:link>
                        </flux:text>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">
                    Guardar
                </flux:button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>