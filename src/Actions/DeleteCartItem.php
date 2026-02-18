<?php

namespace Ingenius\ShopCart\Actions;

use Illuminate\Support\Facades\Config;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Core\Interfaces\IPurchasable;
use Ingenius\ShopCart\Models\CartItem;

class DeleteCartItem
{
    /**
     * Delete a cart item completely
     *
     * @param IPurchasable $productible The polymorphic product model
     * @return bool Whether the deletion was successful
     */
    public function handle(IPurchasable $productible): bool
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

        // Delete the cart item completely
        return (bool) $cartItem->delete();
    }

    /**
     * Delete a cart item completely using the product ID
     *
     * @param int $productId The ID of the product to delete from cart
     * @return bool Whether the deletion was successful
     */
    public function deleteProduct(int $productId): bool
    {
        // Get the product model class from config
        $productModelClass = Config::get('shopcart.product_model', 'Modules\Products\Models\Product');

        // Check if the product model class exists
        if (!class_exists($productModelClass)) {
            return false;
        }

        // Find the product
        $product = $productModelClass::find($productId);

        if (!$product || !($product instanceof IPurchasable)) {
            return false;
        }

        return $this->handle($product);
    }
}
