<?php

namespace Ingenius\ShopCart\Services;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use Ingenius\ShopCart\Models\CartItem;
use Illuminate\Support\Facades\Session;
use Ingenius\Core\Interfaces\IPurchasable;
use Ingenius\Auth\Helpers\AuthHelper;

class ShopCart implements Arrayable, Jsonable
{
    /**
     * Collection of cart items loaded from the database
     *
     * @var Collection
     */
    protected Collection $cartItems;

    /**
     * Cart modifier manager
     *
     * @var CartModifierManager
     */
    protected CartModifierManager $modifierManager;

    /**
     * Constructor that loads cart items
     */
    public function __construct(CartModifierManager $modifierManager)
    {
        $this->modifierManager = $modifierManager;
        $this->cartItems = collect();
        $this->loadCartItems();
    }

    /**
     * Load cart items from the database into the cartItems collection
     *
     * @return void
     */
    protected function loadCartItems(): void
    {
        $query = CartItem::query();
        $user = AuthHelper::getUser();

        if ($user) {
            // If user is authenticated, load items by owner
            $query->where('owner_id', $user->id)
                ->where('owner_type', get_class($user));
        } else {
            // If user is not authenticated, load items by session ID
            $sessionId = Session::getId();
            $query->where('session_id', $sessionId);
        }

        // Load items with their productible relationship
        $cartItems = $query->with('productible')->get();

        // Ensure all productibles implement IPurchasable
        $this->cartItems = $cartItems->map(function ($cartItem) {
            // Ensure the productible implements IPurchasable
            if (!($cartItem->productible instanceof IPurchasable)) {
                throw new \InvalidArgumentException(
                    "Cart item productible must implement IPurchasable interface"
                );
            }

            return $cartItem;
        });
    }

    /**
     * Get all cart items
     *
     * @return Collection
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    /**
     * Delete all cart items for the current user or session
     *
     * @return bool
     */
    public function clearCart(): bool
    {
        $query = CartItem::query();
        $user = AuthHelper::getUser();

        if ($user) {
            // If user is authenticated, delete items by owner
            $query->where('owner_id', $user->id)
                ->where('owner_type', get_class($user));
        } else {
            // If user is not authenticated, delete items by session ID
            $sessionId = Session::getId();
            $query->where('session_id', $sessionId);
        }

        // Delete all matching cart items
        $result = $query->delete();

        // Reset the cart items collection
        $this->cartItems = collect();

        return $result > 0;
    }

    /**
     * Calculate the base subtotal of all items in the cart
     * This doesn't include modifiers
     *
     * @return float
     */
    public function calculateBaseSubtotal(): float
    {
        $subtotal = 0;
        foreach ($this->cartItems as $cartItem) {
            $productible = $cartItem->productible;

            // Ensure productible implements IPurchasable
            if (!($productible instanceof IPurchasable)) {
                continue;
            }

            $subtotal += $productible->getFinalPrice() * $cartItem->quantity;
        }

        return $subtotal;
    }

    /**
     * Calculate the total price of all items in the cart
     * This includes all modifiers
     *
     * @return float
     */
    public function calculateTotal(): float
    {
        $baseSubtotal = $this->calculateBaseSubtotal();
        return $this->modifierManager->calculateFinalSubtotal($this, $baseSubtotal);
    }

    /**
     * Get the modifier manager
     *
     * @return CartModifierManager
     */
    public function getModifierManager(): CartModifierManager
    {
        return $this->modifierManager;
    }

    public function toArray(): array
    {
        $baseArray = [
            'items' => $this->cartItems->toArray(),
            'subtotal' => $this->calculateBaseSubtotal(),
            'total' => $this->calculateTotal(),
        ];

        // Run through all modifiers to extend the array
        return $this->modifierManager->extendCartArray($this, $baseArray);
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
