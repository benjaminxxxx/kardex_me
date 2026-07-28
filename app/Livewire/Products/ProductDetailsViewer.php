<?php

namespace App\Livewire\Products;

use App\Models\Product;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductDetailsViewer extends Component
{
    public bool $show = false;
    public ?Product $product = null;

    #[On('open-product-details')]
    public function open(int $productId): void
    {
        $this->product = Product::with([
            'category',
            'unit',
            'explosiveRole',
            'presentations.unit',
            'createdBy', 'updatedBy',
        ])->findOrFail($productId);

        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->product = null;
    }

    public function render()
    {
        return view('livewire.products.product-details-viewer');
    }
}