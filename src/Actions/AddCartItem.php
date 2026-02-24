<?php

namespace Ingenius\ShopCart\Actions;

use Illuminate\Support\Facades\Config;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Core\Interfaces\IInventoriable;
use Ingenius\Core\Interfaces\IPurchasable;
use Ingenius\Core\Interfaces\StockAvailabilityInterface;
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

        $expiresAt = $this->getExpiresAt();

        // Try to find existing cart item
        $cartItem = $query->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->expires_at = $expiresAt;
            $cartItem->save();

            $this->invalidateStockCache($productible);

            return $cartItem;
        }

        // If no existing cart item, create a new one
        $data = [
            'productible_id' => $productible->getId(),
            'productible_type' => get_class($productible),
            'quantity' => $quantity,
            'expires_at' => $expiresAt,
        ];

        if ($user) {
            $data['owner_id'] = $user->id;
            $data['owner_type'] = get_class($user);
        } else {
            $data['guest_token'] = $guestToken;
        }

        $cartItem = CartItem::create($data);

        $this->invalidateStockCache($productible);

        return $cartItem;
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

        // Check stock availability accounting for reservations in carts and orders
        if ($product instanceof IInventoriable && $product->handleStock()) {
            $stockService = $this->resolveStockService();

            if ($stockService) {
                $available = $stockService->getAvailableStock($product);

                if ($available !== null && $available < $quantity) {
                    throw new InsufficientStockException(
                        $productId,
                        $quantity,
                        $available
                    );
                }
            }
        }

        return $this->handle($product, $quantity);
    }

    /**
     * Get the expiration timestamp for a cart item based on config.
     */
    protected function getExpiresAt(): ?\Carbon\Carbon
    {
        $ttl = Config::get('shopcart.cart_item_ttl');

        if ($ttl === null) {
            return null;
        }

        $ttl = (int) $ttl;

        return now()->addMinutes($ttl);
    }

    /**
     * Invalidate the stock availability cache for the given product.
     */
    protected function invalidateStockCache(IPurchasable $productible): void
    {
        if ($productible instanceof IInventoriable && $productible->handleStock()) {
            $stockService = $this->resolveStockService();
            $stockService?->invalidateCache(get_class($productible), $productible->getId());
        }
    }

    /**
     * Resolve the stock availability service from the container.
     * Returns null if no implementation is bound (products package not installed).
     */
    protected function resolveStockService(): ?StockAvailabilityInterface
    {
        if (app()->bound(StockAvailabilityInterface::class)) {
            return app(StockAvailabilityInterface::class);
        }

        return null;
    }
}
