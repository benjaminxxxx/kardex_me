<x-layouts::auth title="Verificación de correo electrónico">
    <div class="mt-4 flex flex-col gap-6">
        <flux:text class="text-center">
            Por favor, verifique su dirección de correo electrónico haciendo clic en el enlace que acabamos de enviar a su correo.
        </flux:text>

        @if (session('status') == 'verification-link-sent')
            <flux:text class="text-center font-medium !text-green-600 !dark:text-green-400">
                Se ha enviado un nuevo enlace de verificación a la dirección de correo electrónico proporcionada durante el registro.
            </flux:text>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <flux:button type="submit" variant="primary" class="w-full">
                    Reenviar correo de verificación
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <flux:button
                    variant="ghost"
                    type="submit"
                    class="text-sm cursor-pointer"
                    data-test="logout-button"
                >
                    Cerrar sesión
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>