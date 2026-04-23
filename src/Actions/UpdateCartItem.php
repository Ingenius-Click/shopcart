<?php

namespace Ingenius\ShopCart\Actions;

use Ingenius\Core\Interfaces\IInventoriable;
use Ingenius\Core\Interfaces\IPurchasable;
use Ingenius\Core\Interfaces\StockAvailabilityInterface;
use Ingenius\ShopCart\Exceptions\InsufficientStockException;
use Ingenius\ShopCart\Models\CartItem;

class UpdateCartItem
{
    public function handle(int $cartItemId, int $quantity)
    {
        // Validate quantity
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be a non-negative integer.');
        }

        // Find the cart item by ID
        $cartItem = CartItem::find($cartItemId);

        if (!$cartItem) {
            return false; // Cart item not found
        }

        if(!$this->checkProductible($cartItem->productible)) {
            // Productible is not valid, just delete the cart item
            $cartItem->delete();
            return false;
        }

        if($quantity === 0) {
            // If quantity is set to zero, delete the cart item
            $cartItem->delete();
            $this->invalidateStockCache($cartItem->productible);
            return null;
        }

        // Update the quantity
        if(!$this->hasEnoughStock($cartItem->productible, $quantity)) {
            return false;
        }

        $cartItem->quantity = $quantity;
        $updated = $cartItem->save();

        if ($updated) {
            $this->invalidateStockCache($cartItem->productible);
        }

        return $updated ? $cartItem : false;
    }

    public function checkProductible($productible): bool {
        if(!$productible || !($productible instanceof IPurchasable)) {
            return false;
        }

        if(!$productible->canBePurchased()) {
            return false;
        }

        return true;
    }

    public function hasEnoughStock($productible, $quantity): bool {
        if($productible instanceof IInventoriable && $productible->handleStock()) {
            if (app()->bound(StockAvailabilityInterface::class)) {
                $availableStock = app(StockAvailabilityInterface::class)
                    ->getAvailableStock($productible);

                if ($availableStock !== null && $availableStock < $quantity) {
                    throw new InsufficientStockException(
                        $productible->id,
                        $quantity,
                        $availableStock
                    );
                }

                return $availableStock >= $quantity;
            }
        }

        return true; // If not inventoriable or stock is not handled, assume it's available
    }

    /**
     * Invalidate the stock availability cache for the given product.
     */
    protected function invalidateStockCache(IPurchasable $productible): void
    {
        if ($productible instanceof IInventoriable && $productible->handleStock()) {
            if (app()->bound(StockAvailabilityInterface::class)) {
                app(StockAvailabilityInterface::class)
                    ->invalidateCache(get_class($productible), $productible->getId());
            }
        }
    }
}