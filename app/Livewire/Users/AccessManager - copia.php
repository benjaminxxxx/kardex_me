<?php

namespace App\Livewire\Users;

use App\Models\Person;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;

class AccessManager extends Component
{
    public bool $show = false;
    public ?int $personId = null;
    public ?string $personDisplayName = null;
    public bool $hasAccount = false;

    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $showPasswordForm = false;
    public bool $confirmingRemoval = false; // 👈 nuevo

    #[On('open-access-manager')]
    public function open(int $personId): void
    {
        $person = Person::with('user')->findOrFail($personId);

        $this->personId = $person->id;
        $this->personDisplayName = $person->display_name;
        $this->hasAccount = (bool) $person->user;
        $this->email = $person->user->email ?? $person->email ?? '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->showPasswordForm = !$this->hasAccount;
        $this->confirmingRemoval = false;
        $this->show = true;
    }

    public function togglePasswordForm(): void
    {
        $this->showPasswordForm = !$this->showPasswordForm;
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function createAccount(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            User::create([
                'person_id' => $this->personId,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);

            $this->hasAccount = true;
            $this->showPasswordForm = false;
            $this->dispatch('user-created');
            Flux::toast('Cuenta de acceso creada correctamente.');
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function updatePassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('person_id', $this->personId)->firstOrFail();
        $user->update(['password' => Hash::make($this->password)]);

        $this->password = '';
        $this->password_confirmation = '';
        $this->showPasswordForm = false;
        session()->flash('success', 'Contraseña actualizada correctamente.');
    }

    public function updateEmail(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'unique:users,email,' . User::where('person_id', $this->personId)->value('id')],
        ]);

        User::where('person_id', $this->personId)->update(['email' => $this->email]);
        session()->flash('success', 'Correo de acceso actualizado.');
    }

    // ------------------------------------------------------------
    // Quitar acceso
    // ------------------------------------------------------------

    public function askRemoveAccess(): void
    {
        $this->confirmingRemoval = true;
    }

    public function cancelRemoveAccess(): void
    {
        $this->confirmingRemoval = false;
    }

    public function removeAccess(): void
    {
        $user = User::where('person_id', $this->personId)->first();

        if ($user) {
            // Cierra sus sesiones activas antes de borrar la cuenta
            \DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        }

        $this->hasAccount = false;
        $this->confirmingRemoval = false;
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->showPasswordForm = true; // vuelve al form de "crear cuenta" ya que ya no tiene


        Flux::toast('Se quitó el acceso al sistema correctamente.');
    }

    public function close(): void
    {
        $this->show = false;
        $this->confirmingRemoval = false;
    }
    public function render()
    {
        return view('livewire.users.access-manager');
    }
}