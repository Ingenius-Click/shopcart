<?php

namespace Ingenius\ShopCart\Interfaces;

use Ingenius\ShopCart\Services\ShopCart;

interface CartModifierInterface
{
    /**
     * Get the priority of this modifier
     * Lower numbers run first
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Calculate a subtotal to be added to the cart
     *
     * @param ShopCart $cart
     * @param float $currentSubtotal The current running subtotal
     * @return float The modified subtotal
     */
    public function calculateSubtotal(ShopCart $cart, float $currentSubtotal): float;

    /**
     * Extend the cart array with additional data
     *
     * @param ShopCart $cart
     * @param array $cartArray The current cart array
     * @return array The modified cart array
     */
    public function extendCartArray(ShopCart $cart, array $cartArray): array;

    /**
     * Get the name of this modifier
     *
     * @return string
     */
    public function getName(): string;
}
