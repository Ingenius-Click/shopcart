<?php

namespace Ingenius\ShopCart\Actions;

use Illuminate\Support\Facades\Config;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Core\Interfaces\IPurchasable;
use Ingenius\ShopCart\Models\CartItem;

class RemoveCartItem
{
    /**
     * Remove a quantity of a productible from the cart
     * If the resulting quantity is <= 0, the cart item will be deleted
     *
     * @param IPurchasable $productible The polymorphic product model
     * @param int $quantity The quantity to remove
     * @return CartItem|null The updated cart item or null if removed/not found
     */
    public function handle(IPurchasable $productible, int $quantity = 1): ?CartItem
    {
        // Get the authenticated user or null if not authenticated
        $user = AuthHelper::getUser();

        // Set up the query to find an existing cart item
        $query = CartItem::query()
            ->where('productible_id', $productible->getId())
            ->where('productible_type', get_class($productible));

        if ($user) {
            // If user is authenticated, search by owner
            $query->where('owner_id', $user->id)
                ->where('owner_type', get_class($user));
        } else {
            $guestToken = request()->header('X-Guest-Token');
            if (!$guestToken) {
                return null;
            }
            $query->where('guest_token', $guestToken);
        }

        // Try to find existing cart item
        $cartItem = $query->first();

        if (!$cartItem) {
            // Cart item not found
            return null;
        }

        // Subtract the quantity
        $cartItem->quantity -= $quantity;

        if ($cartItem->quantity <= 0) {
            // If resulting quantity is zero or negative, delete the item
            $cartItem->delete();
            return null;
        }

        // Save the updated cart item
        $cartItem->save();
        return $cartItem;
    }

    /**
     * Remove a product from the cart using the configured product model
     *
     * @param int $productId The ID of the product to remove
     * @param int $quantity The quantity to remove
     * @return CartItem|null The updated cart item, null if removed or not found
     */
    public function removeProduct(int $productId, int $quantity = 1): ?CartItem
    {
        // Get the product model class from config
        $productModelClass = Config::get('shopcart.product_model', 'Modules\Products\Models\Product');

        // Check if the product model class exists
        if (!class_exists($productModelClass)) {
            return null;
        }

        // Find the product
        $product = $productModelClass::find($productId);

        if (!$product || !($product instanceof IPurchasable)) {
            return null;
        }

        return $this->handle($product, $quantity);
    }
}
