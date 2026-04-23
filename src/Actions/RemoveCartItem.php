<?php

namespace Ingenius\ShopCart\Actions;

use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Core\Interfaces\IInventoriable;
use Ingenius\Core\Interfaces\IPurchasable;
use Ingenius\Core\Interfaces\StockAvailabilityInterface;
use Ingenius\ShopCart\Models\CartItem;

class RemoveCartItem
{
    /**
     * Remove a quantity of a productible from the cart
     * If the resulting quantity is <= 0, the cart item will be deleted
     *
     * @param CartItem $cartItem The cart item to update
     * @param int $quantity The quantity to remove
     * @return CartItem|null The updated cart item or null if removed/not found
     */
    public function handle(CartItem $cartItem, int $quantity = 1): ?CartItem
    {
        // Get the authenticated user or null if not authenticated
        $user = AuthHelper::getUser();

        if ($user) {
            // If user is authenticated, search by owner
            if($cartItem->owner_id !== $user->id || $cartItem->owner_type !== get_class($user)) {
                return null; // Cart item does not belong to the user
            }
        } else {
            $guestToken = request()->header('X-Guest-Token');
            if (!$guestToken) {
                return null;
            }
            if($cartItem->guest_token !== $guestToken) {
                return null; // Cart item does not belong to the guest
            }
        }

        // Subtract the quantity
        $cartItem->quantity -= $quantity;

        if ($cartItem->quantity <= 0) {
            // If resulting quantity is zero or negative, delete the item
            $cartItem->delete();
            $this->invalidateStockCache($cartItem->productible);
            return null;
        }

        // Save the updated cart item
        $cartItem->save();

        $this->invalidateStockCache($cartItem->productible);

        return $cartItem;
    }

    public function removeCartItemById(int $cartItemId, int $quantity = 1): ?CartItem
    {
        $cartItem = CartItem::find($cartItemId);

        if (!$cartItem) {
            return null;
        }

        $productible = $cartItem->productible;

        if (!$productible || !($productible instanceof IPurchasable)) {
            return null;
        }

        return $this->handle($cartItem, $quantity);
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
