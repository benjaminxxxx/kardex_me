<?php

use Illuminate\Support\Facades\Route;

use App\Constants\Permisos;

use App\Livewire\Employees\EmployeeForm;
use App\Livewire\Employees\EmployeeList;

use App\Livewire\Products\ProductForm;
use App\Livewire\Products\ProductList;

use App\Livewire\Suppliers\SupplierWizard;
use App\Livewire\Suppliers\SupplierList;

use App\Livewire\Entries\EntryForm;
use App\Livewire\Entries\EntryList;

use App\Livewire\Outputs\OutputForm;
use App\Livewire\Outputs\OutputList;

use App\Livewire\Zones\ZoneForm;
use App\Livewire\Zones\ZoneList;

use App\Livewire\Kardex\KardexList;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::view('/', 'dashboard')->name('home');
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // =========================================================================
    // DOMINIO: KARDEX
    // =========================================================================

    Route::prefix('entradas')->group(function () {

        Route::get('/', EntryList::class)
            ->middleware('can:' . Permisos::ENTRADAS_VER)
            ->name('entries.index');

        Route::middleware('can:' . Permisos::ENTRADAS_GESTIONAR)->group(function () {
            Route::get('/crear', EntryForm::class)->name('entries.create');
        });
    });

    Route::prefix('salidas')->group(function () {

        Route::get('/', OutputList::class)
            ->middleware('can:' . Permisos::SALIDAS_VER)
            ->name('outputs.index');

        Route::middleware('can:' . Permisos::SALIDAS_GESTIONAR)->group(function () {
            Route::get('/crear', OutputForm::class)->name('outputs.create');
        });
    });

    Route::prefix('zonas')->group(function () {

        Route::get('/', ZoneList::class)
            ->middleware('can:' . Permisos::ZONAS_VER)
            ->name('zones.index');

        Route::middleware('can:' . Permisos::ZONAS_GESTIONAR)->group(function () {
            Route::get('/crear', ZoneForm::class)->name('zones.create');
        });
    });

    Route::prefix('kardex')->group(function () {

        Route::get('/', KardexList::class)
            ->middleware('can:' . Permisos::KARDEX_VER)
            ->name('kardex.index');
    });

    // =========================================================================
    // DOMINIO: ALMACÉN / INVENTARIO
    // =========================================================================

    Route::prefix('productos')->group(function () {

        Route::get('/', ProductList::class)
            ->middleware('can:' . Permisos::PRODUCTOS_VER)
            ->name('products.index');

        Route::middleware('can:' . Permisos::PRODUCTOS_GESTIONAR)->group(function () {
            Route::get('/crear', ProductForm::class)->name('products.create');
        });
    });

    // =========================================================================
    // DOMINIO: COMPRAS
    // =========================================================================

    Route::prefix('proveedores')->group(function () {

        Route::get('/', SupplierList::class)
            ->middleware('can:' . Permisos::PROVEEDORES_VER)
            ->name('suppliers.index');

        Route::middleware('can:' . Permisos::PROVEEDORES_GESTIONAR)->group(function () {
            Route::get('/crear', SupplierWizard::class)->name('suppliers.create');
        });
    });

    // =========================================================================
    // DOMINIO: RECURSOS HUMANOS
    // =========================================================================

    Route::prefix('trabajadores')->group(function () {

        Route::get('/', EmployeeList::class)
            ->middleware('can:' . Permisos::EMPLEADOS_VER)
            ->name('employees.index');

        Route::middleware('can:' . Permisos::EMPLEADOS_GESTIONAR)->group(function () {
            Route::get('/crear', EmployeeForm::class)->name('employees.create');
        });
    });

});

require __DIR__.'/settings.php';