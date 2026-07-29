<?php

use App\Livewire\ExplosiveDispatches\ExplosiveDistributionReportList;
use App\Livewire\ExplosiveDispatches\ExplosiveFieldDispatchList;
use App\Livewire\ExplosiveDispatches\ExplosiveFieldDistributionForm;
use App\Livewire\Kardex\KardexShow;
use App\Livewire\Kardex\KardexWizard;
use App\Livewire\MiningLabors\MiningLaborList;
use App\Livewire\MiningLabors\MiningLaborForm;
use App\Livewire\Purchases\PurchaseForm;
use App\Livewire\Purchases\PurchaseList;
use App\Livewire\Settings\CompanySettingForm;
use App\Livewire\StockMovements\StockMovementList;
use Illuminate\Support\Facades\Route;

use App\Constants\Permisos;

use App\Livewire\Employees\EmployeeForm;
use App\Livewire\Employees\EmployeeList;

use App\Livewire\Products\ProductForm;
use App\Livewire\Products\ProductList;

use App\Livewire\Suppliers\SupplierWizard;
use App\Livewire\Suppliers\SupplierList;

use App\Livewire\Kardex\KardexList;

use App\Livewire\ExplosiveDispatches\ExplosiveFieldDispatchForm;

use App\Livewire\Roles\RoleList;
use App\Livewire\Roles\RolePermissionsManager;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::view('/', 'dashboard')->name('home');
    Route::view('/dashboard', 'dashboard')->name('dashboard');



    Route::prefix('roles')->group(function () {

        Route::get('/', RoleList::class)
            ->middleware('can:' . Permisos::ROLES_VER)
            ->name('roles.index');

        Route::middleware('can:' . Permisos::ROLES_GESTIONAR)->group(function () {
            Route::get('/{role}/permisos', RolePermissionsManager::class)->name('roles.permissions');
        });
    });

    // =========================================================================
    // DOMINIO: KARDEX
    // =========================================================================

    Route::get('/movimientos', StockMovementList::class)
        ->middleware('can:' . Permisos::MOVIMIENTOS_VER)
        ->name('stock-movements.index');
    Route::prefix('kardex')->group(function () {

        Route::get('/', KardexList::class)
            ->middleware('can:' . Permisos::KARDEX_VER)
            ->name('kardex.index');

        Route::get('/{kardex}', KardexShow::class)
            ->middleware('can:' . Permisos::KARDEX_GESTIONAR)
            ->name('kardex.show');

        Route::middleware('can:' . Permisos::KARDEX_GESTIONAR)->group(function () {
            Route::get('/crear/wizard', KardexWizard::class)->name('kardex.wizard');
        });
    });

    Route::prefix('despacho-explosivos')->group(function () {

        Route::get('/', ExplosiveFieldDispatchList::class)
            ->middleware('can:' . Permisos::DESPACHOS_EXPLOSIVOS_VER)
            ->name('explosive-dispatches.index');

        Route::middleware('can:' . Permisos::EXPLOSIVOS_DESPACHO_REGISTRAR)->group(function () {
            Route::get('/crear', ExplosiveFieldDispatchForm::class)->name('explosive-dispatches.create');
        });

        Route::middleware('can:' . Permisos::EXPLOSIVOS_DISTRIBUIR)->group(function () {
            Route::get('/{dispatch}/distribuir', ExplosiveFieldDistributionForm::class)
                ->name('explosive-dispatches.distribute');
        });

        Route::get('/reporte-distribucion', ExplosiveDistributionReportList::class)
            ->middleware('can:' . Permisos::DISTRIBUCION_REPORTE_VER)
            ->name('explosive-distribution-report.index');
    });

    Route::prefix('labores')->group(function () {

        Route::get('/', MiningLaborList::class)
            ->middleware('can:' . Permisos::LABORES_VER)
            ->name('zones.index');

        Route::middleware('can:' . Permisos::LABORES_GESTIONAR)->group(function () {
            Route::get('/crear', MiningLaborForm::class)->name('zones.create');
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
            Route::get('/{product}/editar', ProductForm::class)->name('products.edit');
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
    Route::prefix('compras')->group(function () {

        Route::get('/', PurchaseList::class)
            ->middleware('can:' . Permisos::COMPRAS_VER)
            ->name('purchases.index');

        Route::middleware('can:' . Permisos::COMPRAS_GESTIONAR)->group(function () {
            Route::get('/crear', PurchaseForm::class)->name('purchases.create');
            Route::get('/{purchase}/editar', PurchaseForm::class)->name('purchases.edit');
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

    Route::get('/configuracion', CompanySettingForm::class)
        ->middleware('can:' . Permisos::CONFIGURACION_VER)
        ->name('settings.company');

});

require __DIR__ . '/settings.php';