<?php

namespace Ingenius\ShopCart\Services;

use Ingenius\ShopCart\Interfaces\CartModifierInterface;

class CartModifierManager
{
    /**
     * Collection of registered modifiers
     *
     * @var array
     */
    protected array $modifiers = [];

    /**
     * Register a new cart modifier
     *
     * @param CartModifierInterface $modifier
     * @return void
     */
    public function register(CartModifierInterface $modifier): void
    {
        $this->modifiers[] = $modifier;
        // Sort modifiers by priority when a new one is added
        $this->sortModifiers();
    }

    /**
     * Get all registered modifiers, sorted by priority
     *
     * @return array
     */
    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    /**
     * Calculate the final subtotal by running through all modifiers
     *
     * @param ShopCart $cart
     * @param float $initialSubtotal
     * @return float
     */
    public function calculateFinalSubtotal(ShopCart $cart, float $initialSubtotal): float
    {
        $subtotal = $initialSubtotal;

        foreach ($this->modifiers as $modifier) {
            $subtotal = $modifier->calculateSubtotal($cart, $subtotal);
        }

        return $subtotal;
    }

    /**
     * Extend the cart array with data from all modifiers
     *
     * @param ShopCart $cart
     * @param array $cartArray
     * @return array
     */
    public function extendCartArray(ShopCart $cart, array $cartArray): array
    {
        $result = $cartArray;

        foreach ($this->modifiers as $modifier) {
            $result = $modifier->extendCartArray($cart, $result);
        }

        return $result;
    }

    /**
     * Sort modifiers by priority
     *
     * @return void
     */
    protected function sortModifiers(): void
    {
        usort($this->modifiers, function (CartModifierInterface $a, CartModifierInterface $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
    }
}
