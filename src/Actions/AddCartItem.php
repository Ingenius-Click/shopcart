<?php

namespace Ingenius\ShopCart\Actions;

use Illuminate\Support\Facades\Config;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Core\Interfaces\IInventoriable;
use Ingenius\Core\Interfaces\IPurchasable;
use Ingenius\ShopCart\Exceptions\InsufficientStockException;
use Ingenius\ShopCart\Models\CartItem;

class AddCartItem
{
    /**
     * Add a productible with a quantity to the cart
     * If the productible already exists for the user/session, add to the existing quantity
     *
     * @param IPurchasable $productible The polymorphic product model
     * @param int $quantity The quantity to add
     * @return CartItem The created or updated cart item
     */
    public function handle(IPurchasable $productible, int $quantity = 1): CartItem
    {
        // Get the authenticated user or null if not authenticated
        $user = AuthHelper::getUser();

        // Set up the query to find an existing cart item
        $query = CartItem::query()
            ->where('productible_id', $productible->getId())
            ->where('productible_type', get_class($productible));

        $guestToken = $user ? null : request()->header('X-Guest-Token');

        if ($user) {
            $query->where('owner_id', $user->id)
                ->where('owner_type', get_class($user));
        } elseif ($guestToken) {
            $query->where('guest_token', $guestToken);
        } else {
            throw new \RuntimeException('Cannot add to cart without an authenticated user or X-Guest-Token header.');
        }

        // Try to find existing cart item
        $cartItem = $query->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->save();

            return $cartItem;
        }

        // If no existing cart item, create a new one
        $data = [
            'productible_id' => $productible->getId(),
            'productible_type' => get_class($productible),
            'quantity' => $quantity,
        ];

        if ($user) {
            $data['owner_id'] = $user->id;
            $data['owner_type'] = get_class($user);
        } else {
            $data['guest_token'] = $guestToken;
        }

        return CartItem::create($data);
    }

    /**
     * Add a product to the cart using the configured product model
     *
     * @param int $productId The ID of the product to add
     * @param int $quantity The quantity to add
     * @return CartItem|null The created or updated cart item, or null if product not found
     * @throws InsufficientStockException When there is not enough stock
     */
    public function addProduct(int $productId, int $quantity = 1): ?CartItem
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

        // Check if the product can be purchased
        if (!$product->canBePurchased()) {
            return null;
        }

        // Check if product implements IInventoriable and has stock management
        if ($product instanceof IInventoriable && $product->handleStock()) {
            // Check if there's enough stock
            if (!$product->hasEnoughStock($quantity)) {
                throw new InsufficientStockException(
                    $productId,
                    $quantity,
                    $product->getStock()
                );
            }
        }

        return $this->handle($product, $quantity);
    }
}
