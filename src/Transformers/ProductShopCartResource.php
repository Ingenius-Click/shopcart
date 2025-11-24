<?php

namespace Ingenius\ShopCart\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Ingenius\Core\Interfaces\IPurchasable;
use Ingenius\Core\Services\PackageHookManager;

class ProductShopCartResource extends JsonResource 
{
    public function toArray(Request $request): array 
    {
        $finalPrice = $this->resource?->productible instanceof IPurchasable ? $this->resource->productible->getFinalPrice() : null;

        // Apply product extensions
        $hookManager = App::make(PackageHookManager::class);

        $extraData = $hookManager->execute('product.cart.array.extend', [],  [
            'product_id' => $this->resource->productible->id,
            'product_class' => get_class($this->resource->productible),
            'quantity' => $this->resource->quantity,
            'base_price' => $this->resource->productible->sale_price,
            'regular_price' => $this->resource->productible->getRegularPrice(),
        ]);

        return [
            ... $this->resource->toArray(),
            ... $finalPrice ? [
                'productible' => [
                    ...$this->resource->productible->toArray(),
                    'sale_price' => $finalPrice,
                ]
            ] : [],
            ... $extraData
        ];
    }
}