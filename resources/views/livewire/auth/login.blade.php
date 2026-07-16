<x-layouts::auth title="Iniciar sesión">
    <div class="flex flex-col gap-6">

        <x-auth-header
            title="Acceso al sistema"
            description="Ingrese su correo electrónico y contraseña para acceder al sistema."
        />

        <!-- Estado de la sesión -->
        <x-auth-session-status
            class="text-center"
            :status="session('status')"
        />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Correo electrónico -->
            <flux:input
                name="email"
                label="Correo electrónico"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="correo@empresa.com"
            />

            <!-- Contraseña -->
            <div class="relative">
                <flux:input
                    name="password"
                    label="Contraseña"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Ingrese su contraseña"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link
                        class="absolute top-0 end-0 text-sm"
                        :href="route('password.request')"
                        wire:navigate
                    >
                        ¿Olvidó su contraseña?
                    </flux:link>
                @endif
            </div>

            <!-- Recordarme -->
            <flux:checkbox
                name="remember"
                label="Recordar mi sesión"
                :checked="old('remember')"
            />

            <div class="flex items-center justify-end">
                <flux:button
                    variant="primary"
                    type="submit"
                    class="w-full"
                    data-test="login-button"
                >
                    Iniciar sesión
                </flux:button>
            </div>
        </form>

    </div>
</x-layouts::auth>