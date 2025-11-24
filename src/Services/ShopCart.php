<?php

namespace Ingenius\ShopCart\Services;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use Ingenius\Core\Services\PackageHookManager;
use Ingenius\ShopCart\Models\CartItem;
use Illuminate\Support\Facades\Session;
use Ingenius\Core\Interfaces\IPurchasable;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\ShopCart\Transformers\ProductShopCartResource;

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

    protected PackageHookManager $hookManager;

    protected ?array $cartDiscounts = null;

    protected ?array $cartExtraCharges = null;

    /**
     * Flag to prevent infinite recursion when calculating discounts
     */
    protected static bool $isCalculatingDiscounts = false;

    /**
     * Flag to prevent infinite recursion when calculating extra charges
     */
    protected static bool $isCalculatingExtraCharges = false;

    /**
     * Constructor that loads cart items
     */
    public function __construct(CartModifierManager $modifierManager)
    {
        $this->modifierManager = $modifierManager;
        $this->cartItems = collect();
        $this->loadCartItems();
        $this->hookManager = app(PackageHookManager::class);
    }

    /**
     * Get cart discounts with lazy loading and recursion prevention
     */
    protected function getCartDiscounts(): array
    {
        if ($this->cartDiscounts !== null) {
            return $this->cartDiscounts;
        }

        if (self::$isCalculatingDiscounts) {
            return [];
        }

        self::$isCalculatingDiscounts = true;
        try {
            $this->cartDiscounts = $this->hookManager->execute(
                'cart.discounts.get',
                [],
                []
            );
        } finally {
            self::$isCalculatingDiscounts = false;
        }

        return $this->cartDiscounts;
    }

    /**
     * Get cart extra charges with lazy loading and recursion prevention
     */
    protected function getCartExtraCharges(): array
    {
        if ($this->cartExtraCharges !== null) {
            return $this->cartExtraCharges;
        }

        if (self::$isCalculatingExtraCharges) {
            return [];
        }

        self::$isCalculatingExtraCharges = true;
        try {
            $this->cartExtraCharges = $this->hookManager->execute(
                'cart.charges.extra.get',
                [],
                []
            );
        } finally {
            self::$isCalculatingExtraCharges = false;
        }

        return $this->cartExtraCharges;
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
                ->where('owner_type', tenant_user_class());
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

            return new ProductShopCartResource($cartItem);
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
        return $this->calculateSubtotal();
    }

    public function calculateSubtotalWithNoCartDiscounts(): float
    {
        return $this->calculateSubtotal(true);
    }

    protected function calculateSubtotal(bool $withFinalPrice = false): float
    {
        $subtotal = 0;
        foreach ($this->cartItems as $cartItem) {
            $productible = $cartItem->productible;

            // Ensure productible implements IPurchasable
            if (!($productible instanceof IPurchasable)) {
                continue;
            }

            $price = $withFinalPrice ? $productible->getFinalPrice() : $productible->sale_price;

            $subtotal += $price * $cartItem->quantity;
        }

        return $subtotal;
    }


    public function calculateFinalSubtotal(): float {
        $finalSubtotal = $this->calculateSubtotal(true);

        $discounts = collect($this->getCartDiscounts())->sum(function($modifier) {
            return $modifier['amount_saved'] ?? 0;
        });

        return $finalSubtotal - $discounts;
    }

    /**
     * Calculate the total price of all items in the cart
     * This includes all modifiers
     *
     * @return float
     */
    public function calculateTotal(): float
    {
        $subtotal = $this->calculateFinalSubtotal();

        $extraCharges = collect($this->getCartExtraCharges())->sum(function($charge) {
            return $charge['amount'] ?? 0;
        });

        return $subtotal + $extraCharges;
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
            'subtotal' => $this->calculateFinalSubtotal(),
            'total' => $this->calculateTotal(),
            'cart_discounts' => $this->getCartDiscounts(),
            'extra_charges' => $this->getCartExtraCharges(),
        ];

        // Run through all modifiers to extend the array
        return $this->modifierManager->extendCartArray($this, $baseArray);
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get all discount information for order creation
     * This collects both product-level and cart-level discounts
     *
     * @return array Contains 'product_discounts' and 'cart_discounts'
     */
    public function getDiscountsForOrder(): array
    {
        $productDiscounts = [];

        foreach ($this->cartItems as $cartItem) {
            $itemArray = $cartItem->toArray(request());

            if (!empty($itemArray['applied_discounts']['discounts'])) {
                foreach ($itemArray['applied_discounts']['discounts'] as $discount) {
                    $productDiscounts[] = [
                        'campaign_id' => $discount['campaign_id'],
                        'campaign_name' => $discount['campaign_name'],
                        'discount_type' => $discount['discount_type'],
                        'amount_saved' => $discount['amount_saved'],
                        'productible_id' => $cartItem->productible_id,
                        'productible_type' => $cartItem->productible_type,
                        'quantity' => $cartItem->quantity,
                    ];
                }
            }
        }

        return [
            'product_discounts' => $productDiscounts,
            'cart_discounts' => $this->getCartDiscounts(),
        ];
    }
}
