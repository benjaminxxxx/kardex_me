<x-layouts::auth title="Recuperar contraseña">
    <div class="flex flex-col gap-6">

        <x-auth-header
            title="¿Olvidó su contraseña?"
            description="Ingrese su correo electrónico para recibir un enlace de restablecimiento de contraseña."
        />

        <!-- Estado de la sesión -->
        <x-auth-session-status
            class="text-center"
            :status="session('status')"
        />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Correo electrónico -->
            <flux:input
                name="email"
                label="Correo electrónico"
                type="email"
                required
                autofocus
                placeholder="correo@empresa.com"
            />

            <flux:button
                variant="primary"
                type="submit"
                class="w-full"
                data-test="email-password-reset-link-button"
            >
                Enviar enlace de recuperación
            </flux:button>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-400 rtl:space-x-reverse">
            <span>O vuelva a</span>

            <flux:link
                :href="route('login')"
                wire:navigate
            >
                iniciar sesión
            </flux:link>
        </div>

    </div>
</x-layouts::auth>