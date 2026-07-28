<?php

namespace App\Livewire\Products;

use App\Models\ExplosiveRole;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPresentation;
use App\Models\Unit;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Producto')]
class ProductForm extends Component
{
    public string $mode = 'create'; // create | edit
    public ?int $productId = null;
    public string $activeTab = 'general';

    // ===== Datos generales =====
    public string $code = '';
    public string $name = '';
    public ?string $chemicalName = null;
    public ?string $brand = null;
    public ?int $categoryId = null;
    public ?int $unitId = null;
    public ?string $barcode = null;
    public ?string $sunatProductCode = null;
    public string $notes = '';
    public bool $isActive = true;
    public ?int $explosiveRoleId = null;
    public int $validationCount = 0;

    // ===== Presentaciones =====
    public array $presentations = [];

    public function mount(?Product $product = null): void
    {
        $this->activeTab = request()->query('tab', 'general');

        if ($product && $product->exists) {
            $this->mode = 'edit';
            $this->productId = $product->id;
            $this->code = $product->code;
            $this->name = $product->name;
            $this->chemicalName = $product->chemical_name;
            $this->brand = $product->brand;
            $this->categoryId = $product->category_id;
            $this->unitId = $product->unit_id;
            $this->explosiveRoleId = $product->explosive_role_id;
            $this->barcode = $product->barcode;
            $this->sunatProductCode = $product->sunat_product_code;
            $this->notes = $product->notes ?? '';
            $this->isActive = $product->is_active;

            $this->presentations = $product->presentations->map(fn($p) => [
                'unit_id' => $p->unit_id,
                'name' => $p->name,
                'conversion_factor' => (string) $p->conversion_factor,
                'is_default_purchase' => $p->is_default_purchase,
                'is_active' => $p->is_active,
            ])->toArray();
        }

        if (empty($this->presentations)) {
            $this->presentations = [$this->emptyPresentation()];
        }
    }
    public function getExplosiveRolesProperty()
    {
        return ExplosiveRole::where('is_active', true)->orderBy('sort_order')->get();
    }
    private function emptyPresentation(): array
    {
        return [
            'unit_id' => null,
            'name' => '',
            'conversion_factor' => '',
            'is_default_purchase' => false,
            'is_active' => true,
        ];
    }

    public function getCategoriesProperty()
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getUnitsProperty()
    {
        return Unit::query()
            ->where(fn($q) => $q->where('is_active', true)->orWhere('id', $this->unitId))
            ->orderBy('name')
            ->get();
    }

    public function addPresentation(): void
    {
        $this->presentations[] = $this->emptyPresentation();
    }

    public function removePresentation(int $index): void
    {
        unset($this->presentations[$index]);
        $this->presentations = array_values($this->presentations);
    }

    private function rules(): array
    {
        $codeUnique = Rule::unique('products', 'code')->ignore($this->productId);
        $barcodeUnique = Rule::unique('products', 'barcode')->ignore($this->productId);

        return [
            'code' => ['required', 'string', 'max:30', $codeUnique],
            'name' => ['required', 'string', 'max:255'],
            'chemicalName' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'categoryId' => ['required', 'exists:product_categories,id'],
            'unitId' => ['required', 'exists:units,id'],
            'barcode' => ['nullable', 'string', 'max:50', $barcodeUnique],
            'sunatProductCode' => ['nullable', 'string', 'max:20'],
            'presentations.*.name' => ['required', 'string', 'max:255'],
            'presentations.*.conversion_factor' => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    public function save(): void
    {
        try {
            $this->validate($this->rules());
        } catch (ValidationException $e) {

            $errors = array_keys($e->validator->errors()->messages());

            if (collect($errors)->contains(fn($field) => str_starts_with($field, 'presentations.'))) {
                $this->activeTab = 'presentations';
            } else {
                $this->activeTab = 'general';
            }
            $this->validationCount++;

            throw $e;
        }

        try {
            DB::transaction(function () {
                $payload = [
                    'code' => $this->code,
                    'name' => $this->name,
                    'chemical_name' => $this->chemicalName,
                    'brand' => $this->brand,
                    'category_id' => $this->categoryId,
                    'unit_id' => $this->unitId,
                    'explosive_role_id' => $this->explosiveRoleId ?: null,
                    'barcode' => $this->barcode,
                    'sunat_product_code' => $this->sunatProductCode,
                    'notes' => $this->notes,
                    'is_active' => $this->isActive,
                ];

                if ($this->mode === 'edit') {
                    $product = Product::findOrFail($this->productId);
                    $product->update($payload);
                    $product->presentations()->delete();
                } else {
                    $product = Product::create($payload);
                }

                foreach ($this->presentations as $presentation) {
                    ProductPresentation::create([
                        'product_id' => $product->id,
                        'unit_id' => $presentation['unit_id'] ?: null,
                        'name' => $presentation['name'],
                        'conversion_factor' => $presentation['conversion_factor'],
                        'is_default_purchase' => $presentation['is_default_purchase'],
                        'is_active' => $presentation['is_active'],
                    ]);
                }

                $this->productId = $product->id;
            });

            Flux::toast($this->mode === 'edit' ? 'Producto actualizado correctamente.' : 'Producto registrado correctamente.');
            $this->redirect(route('products.index'), navigate: true);
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function render()
    {
        return view('livewire.products.product-form');
    }
}