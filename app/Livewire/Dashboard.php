<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $totalUsuarios = User::count();
        $usuariosActivos = User::whereNotNull('email_verified_at')->count();
        $usuariosNuevosMes = User::where('created_at', '>=', now()->startOfMonth())->count();

        $ultimosUsuarios = User::latest()->take(5)->get();

        // Si usas spatie/laravel-permission
        $totalRoles = class_exists(\Spatie\Permission\Models\Role::class)
            ? \Spatie\Permission\Models\Role::count()
            : 0;

        return view('livewire.dashboard', [
            'totalUsuarios' => $totalUsuarios,
            'usuariosActivos' => $usuariosActivos,
            'usuariosNuevosMes' => $usuariosNuevosMes,
            'ultimosUsuarios' => $ultimosUsuarios,
            'totalRoles' => $totalRoles,
        ]);
    }
}