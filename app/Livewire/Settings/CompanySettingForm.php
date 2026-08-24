<?php

namespace App\Livewire\Settings;

use App\Models\CompanySetting;
use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Configuración')]
class CompanySettingForm extends Component
{
    public string $companyName = '';
    public string $ruc = '';
    public string $fiscalAddress = '';
    public string $mineDispatchWarehouseId = '';
    public string $receptionWarehouseId = '';
    public ?int $purchaseDefaultWarehouseId = null;
    public bool $restrictDistributionToRequester = true;

    public function mount(): void
    {
        $setting = CompanySetting::current();

        $this->companyName = $setting->company_name ?? '';
        $this->ruc = $setting->ruc ?? '';
        $this->fiscalAddress = $setting->fiscal_address ?? '';
        $this->mineDispatchWarehouseId = $setting->mine_dispatch_warehouse_id ? (string) $setting->mine_dispatch_warehouse_id : '';
        $this->receptionWarehouseId = $setting->reception_warehouse_id ? (string) $setting->reception_warehouse_id : '';
        $this->purchaseDefaultWarehouseId = $setting->purchase_default_warehouse_id ?? null;
        $this->restrictDistributionToRequester = $setting->restrict_distribution_to_requester ?? true;
    }

    public function getWarehousesProperty()
    {
        return Warehouse::where('is_active', true)->orderBy('name')->get();
    }

    private function rules(): array
    {
        return [
            'companyName' => ['nullable', 'string', 'max:255'],
            'ruc' => ['nullable', 'digits:11'],
            'fiscalAddress' => ['nullable', 'string', 'max:500'],
            'mineDispatchWarehouseId' => ['nullable', 'exists:warehouses,id'],
            'receptionWarehouseId' => ['nullable', 'exists:warehouses,id'],
            'purchaseDefaultWarehouseId' => ['nullable', 'exists:warehouses,id'],
            'restrictDistributionToRequester' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $this->validate($this->rules());

        try {
            CompanySetting::current()->update([
                'company_name' => $this->companyName ?: null,
                'ruc' => $this->ruc ?: null,
                'fiscal_address' => $this->fiscalAddress ?: null,
                'mine_dispatch_warehouse_id' => $this->mineDispatchWarehouseId ?: null,
                'reception_warehouse_id' => $this->receptionWarehouseId ?: null,
                'purchase_default_warehouse_id' => $this->purchaseDefaultWarehouseId ?: null,
                'restrict_distribution_to_requester' => $this->restrictDistributionToRequester,
            ]);

            Flux::toast('Configuración actualizada correctamente.');
        } catch (\Throwable $th) {
            Flux::toast($th->getMessage(), 'Error');
        }
    }

    public function render()
    {
        return view('livewire.settings.company-setting-form');
    }
}