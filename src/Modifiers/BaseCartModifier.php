<?php

namespace Ingenius\ShopCart\Modifiers;

use Ingenius\ShopCart\Interfaces\CartModifierInterface;
use Ingenius\ShopCart\Services\ShopCart;

abstract class BaseCartModifier implements CartModifierInterface
{
    /**
     * Default priority (middle)
     * Override in your implementation to change order
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Default implementation returns the same subtotal
     * Override to add your own calculations
     *
     * @param ShopCart $cart
     * @param float $currentSubtotal
     * @return float
     */
    public function calculateSubtotal(ShopCart $cart, float $currentSubtotal): float
    {
        return $currentSubtotal;
    }

    /**
     * Default implementation returns the same array
     * Override to add your own data
     *
     * @param ShopCart $cart
     * @param array $cartArray
     * @return array
     */
    public function extendCartArray(ShopCart $cart, array $cartArray): array
    {
        return $cartArray;
    }

    /**
     * Get the class name as the default modifier name
     * Override to provide a custom name
     *
     * @return string
     */
    public function getName(): string
    {
        $className = get_class($this);
        $parts = explode('\\', $className);
        return end($parts);
    }
}
