<?php

namespace Tests\Feature\Users;

use App\Livewire\Users\AccessManager;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Flujo de "Administrar acceso" en empleados: crear cuenta, cambiar clave,
 * cambiar correo, quitar y volver a dar acceso. En cada paso se valida
 * que el usuario realmente pueda iniciar sesión.
 */
class AccessManagerTest extends TestCase
{
    use RefreshDatabase;

    private Person $person;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => 'web']);

        // Usuario que opera el módulo (las columnas de auditoría requieren uno)
        $adminPerson = $this->makePerson('ADM', 'admin@empresa.test');
        $admin = User::create([
            'person_id' => $adminPerson->id,
            'email' => 'admin@empresa.test',
            'password' => 'secreto-admin',
        ]);
        $this->actingAs($admin);

        // El empleado tiene en su ficha (person) un correo distinto al de acceso
        $this->person = $this->makePerson('EMP', 'rosa.personal@gmail.com');
    }

    private function makePerson(string $code, string $email): Person
    {
        return Person::create([
            'code' => $code,
            'type' => 'individual',
            'document_type' => 'DNI',
            'document_number' => (string) random_int(10000000, 99999999),
            'names' => $code,
            'display_name' => $code,
            'email' => $email,
        ]);
    }

    /** Intenta iniciar sesión por la ruta real de login, como lo haría la persona. */
    private function canLogin(string $email, string $password): bool
    {
        $admin = Auth::user();
        Auth::logout();

        $this->post(route('login.store'), ['email' => $email, 'password' => $password]);
        $ok = Auth::check() && Auth::user()->person_id === $this->person->id;

        Auth::logout();
        $this->flushSession();
        $this->actingAs($admin);

        return $ok;
    }

    private function createAccess(string $email, string $password)
    {
        return Livewire::test(AccessManager::class)
            ->call('open', $this->person->id)
            ->set('email', $email)
            ->set('password', $password)
            ->set('password_confirmation', $password)
            ->set('selectedRole', 'Supervisor')
            ->call('createAccount')
            ->assertHasNoErrors();
    }

    public function test_crear_acceso_permite_iniciar_sesion(): void
    {
        $this->createAccess('rosa@empresa.test', 'clave-inicial');

        $this->assertTrue($this->canLogin('rosa@empresa.test', 'clave-inicial'));
    }

    public function test_al_abrir_se_usa_el_correo_de_person_si_no_tiene_cuenta(): void
    {
        Livewire::test(AccessManager::class)
            ->call('open', $this->person->id)
            ->assertSet('email', 'rosa.personal@gmail.com')
            ->assertSet('hasAccount', false);
    }

    public function test_al_abrir_con_cuenta_se_usa_el_correo_de_user(): void
    {
        $this->createAccess('rosa@empresa.test', 'clave-inicial');

        Livewire::test(AccessManager::class)
            ->call('open', $this->person->id)
            ->assertSet('email', 'rosa@empresa.test')
            ->assertSet('hasAccount', true);
    }

    public function test_cambiar_clave_permite_ingresar_con_la_nueva(): void
    {
        $this->createAccess('rosa@empresa.test', 'clave-inicial');

        Livewire::test(AccessManager::class)
            ->call('open', $this->person->id)
            ->call('togglePasswordForm')
            ->set('password', 'clave-nueva-123')
            ->set('password_confirmation', 'clave-nueva-123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue($this->canLogin('rosa@empresa.test', 'clave-nueva-123'));
        $this->assertFalse($this->canLogin('rosa@empresa.test', 'clave-inicial'));
    }

    public function test_actualizar_correo_cambia_el_correo_y_conserva_la_clave(): void
    {
        $this->createAccess('rosa@empresa.test', 'clave-inicial');

        Livewire::test(AccessManager::class)
            ->call('open', $this->person->id)
            ->set('email', 'Rosa.Nueva@Empresa.test')
            ->call('updateEmail')
            ->assertHasNoErrors();

        $this->assertSame('rosa.nueva@empresa.test', User::where('person_id', $this->person->id)->value('email'));
        $this->assertTrue($this->canLogin('rosa.nueva@empresa.test', 'clave-inicial'));
    }

    public function test_actualizar_correo_y_luego_cambiar_clave(): void
    {
        $this->createAccess('rosa@empresa.test', 'clave-inicial');

        Livewire::test(AccessManager::class)
            ->call('open', $this->person->id)
            ->set('email', 'rosa2@empresa.test')
            ->call('updateEmail')
            ->call('togglePasswordForm')
            ->set('password', 'clave-nueva-123')
            ->set('password_confirmation', 'clave-nueva-123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue($this->canLogin('rosa2@empresa.test', 'clave-nueva-123'));
    }

    public function test_quitar_y_volver_a_dar_acceso(): void
    {
        $this->createAccess('rosa@empresa.test', 'clave-inicial');

        Livewire::test(AccessManager::class)
            ->call('open', $this->person->id)
            ->call('removeAccess');

        $this->assertFalse($this->canLogin('rosa@empresa.test', 'clave-inicial'));

        $this->createAccess('rosa@empresa.test', 'otra-clave-1');

        $this->assertTrue($this->canLogin('rosa@empresa.test', 'otra-clave-1'));
    }
}
