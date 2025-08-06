<?php

namespace Ingenius\ShopCart\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class RemoveFromCartFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'remove-from-cart';
    }

    public function getName(): string
    {
        return __('Remove from cart');
    }

    public function getGroup(): string
    {
        return __('Shopcart');
    }

    public function getPackage(): string
    {
        return 'shopcart';
    }

    public function isBasic(): bool
    {
        return true;
    }
}
