<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DeveloperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el rol si no existe
        $developerRole = Role::firstOrCreate([
            'name' => 'Developer',
            'guard_name' => 'web',
        ]);

        // Crear la persona
        $person = Person::firstOrCreate(
            [
                'document_type' => 'DNI',
                'document_number' => '77685850',
            ],
            [
                'code' => 'DEV000001',

                'type' => 'individual',

                'names' => 'Benjamin',
                'paternal_last_name' => 'Quispe',
                'maternal_last_name' => 'Ramos',

                'display_name' => 'Benjamin Quispe',
                'legal_name' => 'Benjamin Quispe',

                'email' => 'developer@localhost',

                'is_active' => true,
            ]
        );

        // Crear el usuario
        $user = User::firstOrCreate(
            [
                'email' => 'benjamin_unitek@hotmail.com',
            ],
            [
                'person_id' => $person->id,

                'password' => Hash::make('masnaki18'),

                'email_verified_at' => now(),
            ]
        );

        $user->assignRole($developerRole);
    }
}
