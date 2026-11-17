<?php

namespace Tests\Feature\Audit;

use App\Constants\Permisos;
use App\Livewire\Audit\AuditLogList;
use App\Livewire\Dashboard;
use App\Livewire\Kardex\KardexList;
use App\Livewire\Products\ProductForm;
use App\Livewire\Purchases\PurchaseList;
use App\Models\AuditLog;
use App\Models\Kardex;
use App\Models\Person;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Models\User;
use App\Services\KardexService;
use App\Services\PurchaseService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CriticalOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private int $warehouseId;
    private int $productId;
    private int $supplierId;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'code' => 'ADM', 'type' => 'individual', 'document_type' => 'DNI',
            'document_number' => '11111111', 'names' => 'Ana', 'display_name' => 'Ana Admin',
        ]);
        $this->admin = User::create(['person_id' => $person->id, 'email' => 'ana@empresa.test', 'password' => 'secreto-123']);

        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        foreach ((new \ReflectionClass(Permisos::class))->getConstants() as $name) {
            if (str_contains($name, '.')) {
                $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
            }
        }
        $this->admin->assignRole($role);
        $this->actingAs($this->admin);

        $this->warehouseId = DB::table('warehouses')->insertGetId(['name' => 'Principal', 'code' => 'ALM1']);
        $unitId = DB::table('units')->insertGetId(['sunat_code' => 'NIU', 'name' => 'Unidad', 'alias' => 'u']);
        $categoryId = DB::table('product_categories')->insertGetId(['name' => 'Explosivos', 'code' => 'EXP']);
        $this->productId = DB::table('products')->insertGetId([
            'code' => 'FUL001', 'name' => 'Fulminante', 'category_id' => $categoryId, 'unit_id' => $unitId,
        ]);

        $supplierPerson = Person::create([
            'code' => 'PRV', 'type' => 'company', 'document_type' => 'RUC',
            'document_number' => '20123456789', 'company_name' => 'Proveedor SAC', 'display_name' => 'Proveedor SAC',
        ]);
        $this->supplierId = DB::table('suppliers')->insertGetId(['person_id' => $supplierPerson->id, 'supplier_code' => 'PRV001']);
    }

    private function createPurchase(float $qty, string $date = null, ?int $presentationId = null): Purchase
    {
        return app(PurchaseService::class)->create(
            [
                'supplier_id' => $this->supplierId,
                'warehouse_id' => $this->warehouseId,
                'document_type' => 'factura',
                'document_series' => 'F001',
                'document_number' => (string) random_int(100, 999),
                'document_date' => $date ?? now()->toDateString(),
            ],
            [[
                'product_id' => $this->productId,
                'presentation_id' => $presentationId,
                'quantity' => $qty,
                'conversion_factor' => 1,
                'unit_cost' => 2,
                'discount_percent' => 0,
                'igv_percent' => 18,
            ]]
        );
    }

    private function stock(): float
    {
        return StockService::available($this->productId, $this->warehouseId);
    }

    public function test_eliminar_compra_exige_motivo(): void
    {
        $purchase = $this->createPurchase(100);

        Livewire::test(PurchaseList::class)
            ->call('confirmDelete', $purchase->id)
            ->assertSet('showDeleteModal', true)
            ->set('deletionReason', '')
            ->call('deletePurchase')
            ->assertHasErrors(['deletionReason' => 'required']);

        $this->assertNotSoftDeleted($purchase);
        $this->assertSame(100.0, $this->stock());
    }

    public function test_eliminar_compra_revierte_stock_y_registra_auditoria(): void
    {
        $purchase = $this->createPurchase(100);
        $this->assertSame(100.0, $this->stock());

        Livewire::test(PurchaseList::class)
            ->call('confirmDelete', $purchase->id)
            ->set('deletionReason', 'Compra registrada dos veces por error')
            ->call('deletePurchase')
            ->assertHasNoErrors();

        $purchase = Purchase::withTrashed()->find($purchase->id);
        $this->assertNotNull($purchase->deleted_at);
        $this->assertSame('Ana Admin', $purchase->deleted_by_name);
        $this->assertSame('Compra registrada dos veces por error', $purchase->deletion_reason);
        $this->assertSame(0.0, $this->stock());

        $log = AuditLog::where('event', 'purchase.deleted')->first();
        $this->assertNotNull($log);
        $this->assertSame('critical', $log->severity);
        $this->assertSame('Ana Admin', $log->user_name);
        $this->assertSame('Compra registrada dos veces por error', $log->reason);
        $this->assertSame(100.0, (float) $log->old_values['items'][0]['cantidad_base']);
        $this->assertTrue(AuditLog::where('event', 'stock_movement.deleted')->exists());
    }

    public function test_no_se_elimina_compra_si_el_stock_ya_se_despacho(): void
    {
        $purchase = $this->createPurchase(100);
        app(StockService::class)->registerMovement('out', $this->productId, $this->warehouseId, 60, now()->toDateString());

        Livewire::test(PurchaseList::class)
            ->call('confirmDelete', $purchase->id)
            ->set('deletionReason', 'Intento de eliminar compra ya consumida')
            ->call('deletePurchase');

        $this->assertNotSoftDeleted($purchase);
        $this->assertSame(40.0, $this->stock());
    }

    public function test_restaurar_compra_vuelve_a_registrar_stock(): void
    {
        $purchase = $this->createPurchase(100);
        app(PurchaseService::class)->delete($purchase, 'Eliminada para probar la restauración');
        $this->assertSame(0.0, $this->stock());

        Livewire::test(PurchaseList::class)->call('restorePurchase', $purchase->id);

        $this->assertNotSoftDeleted($purchase);
        $this->assertSame(100.0, $this->stock());
        $this->assertTrue(AuditLog::where('event', 'purchase.restored')->exists());
    }

    public function test_eliminar_compra_recalcula_kardex_abierto_y_bloquea_si_esta_cerrado(): void
    {
        $purchase = $this->createPurchase(100, '2026-06-10');
        $kardex = app(KardexService::class)->openFirst($this->productId, 2026, 6, 0, 0);
        app(KardexService::class)->calculate($kardex);
        $this->assertEquals(100, $kardex->refresh()->closing_qty);

        app(PurchaseService::class)->delete($purchase, 'Compra anulada por el proveedor');
        $this->assertEquals(0, $kardex->refresh()->closing_qty);

        // Con el kárdex cerrado ya no se puede eliminar
        $second = $this->createPurchase(50, '2026-06-12');
        app(KardexService::class)->calculate($kardex);
        app(KardexService::class)->close($kardex);

        $this->expectExceptionMessage('kárdex cerrado');
        app(PurchaseService::class)->delete($second, 'No debería poder eliminarse');
    }

    public function test_kardex_cerrado_no_se_puede_eliminar_y_abierto_pide_motivo(): void
    {
        $kardex = app(KardexService::class)->openFirst($this->productId, 2026, 6, 0, 0);

        Livewire::test(KardexList::class)
            ->call('confirmDelete', $kardex->id)
            ->set('deletionReason', '')
            ->call('deleteKardex')
            ->assertHasErrors('deletionReason');
        $this->assertModelExists($kardex);

        app(KardexService::class)->close($kardex);

        Livewire::test(KardexList::class)
            ->call('confirmDelete', $kardex->id)
            ->set('deletionReason', 'Intento de borrar un kárdex cerrado')
            ->call('deleteKardex');
        $this->assertModelExists($kardex);
        $this->assertTrue(AuditLog::where('event', 'kardex.closed')->exists());
    }

    public function test_editar_producto_con_compras_conserva_presentaciones_usadas(): void
    {
        $presentation = ProductPresentation::create([
            'product_id' => $this->productId, 'name' => 'Caja x 100', 'conversion_factor' => 100,
            'is_default_purchase' => true, 'is_active' => true,
        ]);
        $this->createPurchase(2, null, $presentation->id);

        // Se quita la presentación usada y se agrega otra: antes esto fallaba por la llave foránea
        Livewire::test(ProductForm::class, ['product' => Product::find($this->productId)])
            ->set('name', 'Fulminante editado')
            ->set('presentations', [[
                'id' => null, 'unit_id' => null, 'name' => 'Caja x 50', 'conversion_factor' => '50',
                'is_default_purchase' => true, 'is_active' => true,
            ]])
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::find($this->productId);
        $this->assertSame('Fulminante editado', $product->name);
        $this->assertSame('FUL001', $product->code, 'El código no debe cambiar al editar');
        $this->assertFalse($presentation->refresh()->is_active, 'La presentación usada se desactiva, no se borra');
        $this->assertTrue($product->presentations()->where('name', 'Caja x 50')->exists());
    }

    public function test_pantalla_de_auditoria_y_dashboard(): void
    {
        $purchase = $this->createPurchase(10);
        app(PurchaseService::class)->delete($purchase, 'Prueba de pantalla de auditoría');

        $this->get(route('audit.index'))->assertOk()->assertSee('Compra eliminada');
        $this->get(route('dashboard'))->assertOk()->assertSee('Actividad crítica reciente');

        Livewire::test(AuditLogList::class)
            ->set('event', 'purchase.deleted')
            ->assertSee('Prueba de pantalla de auditoría')
            ->call('view', AuditLog::where('event', 'purchase.deleted')->value('id'))
            ->assertSee('Datos anteriores');
    }

    public function test_auditoria_requiere_permiso(): void
    {
        $person = Person::create([
            'code' => 'OPE', 'type' => 'individual', 'document_type' => 'DNI',
            'document_number' => '22222222', 'names' => 'Op', 'display_name' => 'Operador',
        ]);
        $user = User::create(['person_id' => $person->id, 'email' => 'op@empresa.test', 'password' => 'secreto-123']);

        $this->actingAs($user)->get(route('audit.index'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_editar_proveedor_conserva_direcciones_y_audita_cambios(): void
    {
        $branchId = DB::table('supplier_branches')->insertGetId([
            'supplier_id' => $this->supplierId, 'type' => 'fiscal', 'name' => 'Oficina Lima', 'is_main' => true,
        ]);
        $accountId = DB::table('supplier_bank_accounts')->insertGetId([
            'supplier_id' => $this->supplierId, 'type' => 'bank_account', 'bank_name' => 'BCP',
            'account_number' => '191-000', 'currency' => 'PEN', 'is_main' => true,
        ]);

        $editor = Livewire::test(\App\Livewire\Suppliers\SupplierEditor::class)
            ->call('open', $this->supplierId);

        $branches = $editor->get('branches');
        $branches[0]['name'] = 'Oficina Arequipa';

        $editor->set('branches', $branches)
            ->set('bankAccounts', [[
                'id' => null, 'type' => 'bank_account', 'bank_name' => 'Interbank', 'account_number' => '200-111',
                'cci' => '', 'currency' => 'PEN', 'wallet_provider' => '', 'wallet_phone' => '',
                'account_holder_name' => '', 'is_main' => true,
            ]])
            ->call('save')
            ->assertHasNoErrors();

        // La dirección se actualizó (mismo id), la cuenta quitada se eliminó y la nueva se creó
        $this->assertSame('Oficina Arequipa', DB::table('supplier_branches')->where('id', $branchId)->value('name'));
        $this->assertDatabaseMissing('supplier_bank_accounts', ['id' => $accountId]);
        $this->assertDatabaseHas('supplier_bank_accounts', ['supplier_id' => $this->supplierId, 'bank_name' => 'Interbank']);

        $log = AuditLog::where('event', 'supplier.contacts_changed')->firstOrFail();
        $this->assertSame('Oficina Lima', $log->new_values['direcciones']['modificadas'][0]['antes']['name']);
        $this->assertSame('191-000', $log->new_values['cuentas_bancarias']['eliminadas'][0]['account_number']);
    }

    public function test_alerta_saldo_inicial_distinto_y_recordatorio_de_cierre(): void
    {
        $service = app(KardexService::class);
        $previousMonth = now()->subMonthNoOverflow();

        $june = $service->openFirst($this->productId, 2026, 6, 100, 1);
        $service->calculate($june);
        $service->close($june);

        // Julio se abre en secuencia (hereda 100) y luego alguien cambia su saldo inicial a 80 por fuera
        $july = $service->openNext($this->productId, 2026);
        $this->assertSame(7, (int) $july->month);
        DB::table('kardexes')->where('id', $july->id)->update(['opening_qty' => 80]);
        $july->refresh();

        $mismatches = $service->openingMismatches();
        $this->assertArrayHasKey($july->id, $mismatches);
        $this->assertEquals(-20, $mismatches[$july->id]['difference']);
        $this->assertArrayNotHasKey($june->id, $mismatches);

        // Julio 2026 sigue abierto y ya terminó (hoy es posterior): debe aparecer como pendiente de cierre
        $this->assertTrue($service->overdueOpen()->contains('id', $july->id));

        Livewire::test(KardexList::class)
            ->assertSee('Recordatorio de cierre mensual')
            ->assertSee('Saldos iniciales que no coinciden');

        // Al corregir el saldo inicial, la alerta desaparece y el cambio queda auditado
        $service->updateOpeningBalance($july, 100, 1);
        $this->assertArrayNotHasKey($july->id, $service->openingMismatches());
        $this->assertTrue(AuditLog::where('event', 'kardex.opening_changed')->exists());
    }

    public function test_el_perfil_ya_no_permite_eliminar_la_cuenta(): void
    {
        $this->get(route('profile.edit'))->assertOk()->assertDontSee('Eliminar cuenta');
        $this->assertFalse(class_exists(\App\Livewire\Settings\DeleteUserForm::class));
    }

    public function test_actualizaciones_visible_para_cualquier_usuario(): void
    {
        $person = Person::create([
            'code' => 'SEC', 'type' => 'individual', 'document_type' => 'DNI',
            'document_number' => '33333333', 'names' => 'Sec', 'display_name' => 'Secretaria',
        ]);
        $user = User::create(['person_id' => $person->id, 'email' => 'sec@empresa.test', 'password' => 'secreto-123']);

        $this->actingAs($user)->get(route('changelog.index'))
            ->assertOk()
            ->assertSee('Actualizaciones')
            ->assertSee('Auditoría');
    }

    public function test_registros_de_auditoria_no_se_pueden_modificar(): void
    {
        $purchase = $this->createPurchase(10);
        app(PurchaseService::class)->delete($purchase, 'Prueba de inmutabilidad');
        $log = AuditLog::first();

        $this->expectException(\LogicException::class);
        $log->update(['reason' => 'alterado']);
    }
}
