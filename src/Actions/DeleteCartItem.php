<?php

namespace Ingenius\ShopCart\Actions;

use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Core\Interfaces\IPurchasable;
use Ingenius\Core\Interfaces\StockAvailabilityInterface;
use Ingenius\ShopCart\Models\CartItem;

class DeleteCartItem
{
    /**
     * Delete a cart item completely
     *
     * @param int $cartItemId The cart item to delete
     * @return bool Whether the deletion was successful
     */
    public function handle(int $cartItemId): bool
    {
        // Get the authenticated user or null if not authenticated
        $user = AuthHelper::getUser();

        // Set up the query to find an existing cart item
        $query = CartItem::query()
            ->where('id', $cartItemId)
            ;

        if ($user) {
            // If user is authenticated, search by owner
            $query->where('owner_id', $user->id)
                ->where('owner_type', get_class($user));
        } else {
            $guestToken = request()->header('X-Guest-Token');
            if (!$guestToken) {
                return false;
            }
            $query->where('guest_token', $guestToken);
        }

        // Try to find existing cart item
        $cartItem = $query->first();

        if (!$cartItem) {
            // Cart item not found
            return false;
        }

        if(!$this->checkProductible($cartItem->productible)) {
            // Productible is not valid, just delete the cart item
            return false;
        }

        // Delete the cart item completely
        $deleted = (bool) $cartItem->delete();

        if ($deleted) {
            $this->invalidateStockCache($cartItem->productible);
        }

        return $deleted;
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
