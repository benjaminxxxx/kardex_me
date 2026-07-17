<?php

use Illuminate\Support\Facades\Route;
use App\Constants\Permisos;
use App\Livewire\Users\UserList;
use App\Livewire\Users\UserForm;

use App\Livewire\Employees\EmployeeList;
use App\Livewire\Employees\EmployeeForm;


Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/', 'dashboard')->name('home');
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // =========================================================================
    // DOMINIO: RECURSOS HUMANOS (Trabajadores)
    // =========================================================================
    
    Route::prefix('trabajadores')->group(function () {
        Route::get('/', EmployeeList::class)
            ->middleware('can:' . Permisos::EMPLEADOS_VER)
            ->name('employees.index');

        Route::middleware('can:' . Permisos::EMPLEADOS_GESTIONAR)->group(function () {
            // Ambos apuntan a EmployeeForm
            Route::get('/crear', EmployeeForm::class)->name('employees.create');
            //Route::get('/editar/{employee:uuid}', EmployeeForm::class)->name('employees.edit');
        });
    });
});

require __DIR__ . '/settings.php';
