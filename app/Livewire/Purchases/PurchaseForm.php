<?php

namespace App\Livewire\Purchases;

use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nueva compra')]
class PurchaseForm extends Component
{
    public ?int $purchaseId = null;
    public string $mode = 'create';

    public ?int $supplierId = null;
    public ?string $supplierLabel = null;

    public string $warehouseId = '';
    public string $currency = 'PEN';
    public string $exchangeRate = '1';
    public string $documentType = 'factura';
    public string $documentSeries = '';
    public string $documentNumber = '';
    public string $documentDate = '';
    public string $dueDate = '';
    public string $paymentMethod = 'contado';
    public string $notes = '';

    public array $items = []; // [{product_id, product_label, presentation_id, conversion_factor, quantity, unit_cost, discount_percent, igv_percent}]

    public function mount(?Purchase $purchase = null): void
    {
        $this->documentDate = now()->format('Y-m-d');
        $this->warehouseId = (string) (CompanySetting::current()->purchase_default_warehouse_id ?? '');

        if ($purchase && $purchase->exists) {
            $this->mode = 'edit';
            $this->purchaseId = $purchase->id;
            $this->supplierId = $purchase->supplier_id;
            $this->supplierLabel = $purchase->supplier->person->display_name;
            $this->warehouseId = (string) $purchase->warehouse_id;
            $this->currency = $purchase->currency;
            $this->exchangeRate = (string) $purchase->exchange_rate;
            $this->documentType = $purchase->document_type;
            $this->documentSeries = $purchase->document_series ?? '';
            $this->documentNumber = $purchase->document_number ?? '';
            $this->documentDate = $purchase->document_date->format('Y-m-d');
            $this->dueDate = $purchase->due_date?->format('Y-m-d') ?? '';
            $this->paymentMethod = $purchase->payment_method;
            $this->notes = $purchase->notes ?? '';

            foreach ($purchase->items as $item) {
                $this->items[] = [
                    'product_id' => $item->product_id,
                    'product_label' => $item->product->brand
                        ? "{$item->product->name} ({$item->product->brand})"
                        : $item->product->name,
                    'presentation_id' => $item->presentation_id ?: '',
                    'conversion_factor' => $item->presentation?->conversion_factor ?? 1,
                    'quantity' => (string) $item->quantity,
                    'unit_cost' => (string) $item->unit_cost,
                    'discount_percent' => (string) $item->discount_percent,
                    'igv_percent' => (string) $item->igv_percent,
                ];
            }
        }
    }

    public function getWarehousesProperty()
    {
        return Warehouse::where('is_active', true)->orderBy('name')->get();
    }

    #[On('entity-selected')]
    public function handleEntitySelected(string $context, int $id, string $label): void
    {
        if ($context === 'purchase-supplier') {
            $this->supplierId = $id;
            $this->supplierLabel = $label;
            return;
        }

        if ($context === 'purchase-product') {
            $product = Product::with('presentations')->find($id);
            if (!$product)
                return;

            $defaultPresentation = $product->presentations->firstWhere('is_default_purchase', true)
                ?? $product->presentations->first();

            $this->items[] = [
                'product_id' => $product->id,
                'product_label' => $product->brand ? "{$product->name} ({$product->brand})" : $product->name,
                'presentation_id' => $defaultPresentation?->id ?? '',
                'conversion_factor' => $defaultPresentation?->conversion_factor ?? 1,
                'quantity' => '1',
                'unit_cost' => '',
                'discount_percent' => '0',
                'igv_percent' => '18',
            ];
        }
    }

    #[On('entity-cleared')]
    public function handleEntityCleared(string $context): void
    {
        if ($context === 'purchase-supplier') {
            $this->supplierId = null;
            $this->supplierLabel = null;
        }
    }

    public function getPresentationOptions(int $index)
    {
        $productId = $this->items[$index]['product_id'] ?? null;
        if (!$productId)
            return collect();

        return Product::find($productId)->presentations()->where('is_active', true)->get();
    }

    public function updatedItems($value, $key): void
    {
        // key llega como "0.presentation_id" — actualizamos el factor de conversión
        if (str_ends_with($key, '.presentation_id')) {
            $index = (int) explode('.', $key)[0];
            $presentationId = $this->items[$index]['presentation_id'];

            $this->items[$index]['conversion_factor'] = $presentationId
                ? \App\Models\ProductPresentation::find($presentationId)?->conversion_factor ?? 1
                : 1;
        }
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function getLineTotalProperty(): array
    {
        return collect($this->items)->map(function ($item) {
            $base = (float) ($item['quantity'] ?: 0) * (float) ($item['unit_cost'] ?: 0);
            $descuento = $base * ((float) ($item['discount_percent'] ?: 0) / 100);
            $neto = $base - $descuento;
            $igv = $neto * ((float) ($item['igv_percent'] ?: 0) / 100);
            return round($neto + $igv, 2);
        })->toArray();
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            $base = (float) ($item['quantity'] ?: 0) * (float) ($item['unit_cost'] ?: 0);
            $descuento = $base * ((float) ($item['discount_percent'] ?: 0) / 100);
            return round($base - $descuento, 2);
        });
    }

    public function getIgvTotalProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            $base = (float) ($item['quantity'] ?: 0) * (float) ($item['unit_cost'] ?: 0);
            $descuento = $base * ((float) ($item['discount_percent'] ?: 0) / 100);
            $neto = $base - $descuento;
            return round($neto * ((float) ($item['igv_percent'] ?: 0) / 100), 2);
        });
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal + $this->igvTotal;
    }

    public function save(PurchaseService $service): void
    {
        $this->validate([
            'supplierId' => ['required', 'exists:suppliers,id'],
            'warehouseId' => ['required', 'exists:warehouses,id'],
            'documentDate' => ['required', 'date'],
            'documentSeries' => ['nullable', 'string', 'max:10'],
            'documentNumber' => ['nullable', 'string', 'max:20'],
        ]);

        if (empty($this->items)) {
            Flux::toast('Agrega al menos un producto a la compra.', 'Error');
            return;
        }

        foreach ($this->items as $item) {
            if (!filled($item['unit_cost']) || (float) $item['unit_cost'] <= 0) {
                Flux::toast('Todos los productos deben tener un costo unitario válido.', 'Error');
                return;
            }
        }

        $header = [
            'supplier_id' => $this->supplierId,
            'warehouse_id' => $this->warehouseId,
            'currency' => $this->currency,
            'exchange_rate' => $this->currency === 'USD' ? $this->exchangeRate : 1,
            'document_type' => $this->documentType,
            'document_series' => $this->documentSeries ?: null,
            'document_number' => $this->documentNumber ?: null,
            'document_date' => $this->documentDate,
            'due_date' => $this->dueDate ?: null,
            'payment_method' => $this->paymentMethod,
            'notes' => $this->notes,
        ];

        try {
            if ($this->mode === 'edit') {
                $purchase = Purchase::findOrFail($this->purchaseId);
                $service->update($purchase, $header, $this->items);
            } else {
                $service->create($header, $this->items);
            }

            Flux::toast('Compra registrada correctamente.');
            $this->redirect(route('purchases.index'), navigate: true);
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function render()
    {
        return view('livewire.purchases.purchase-form');
    }
}