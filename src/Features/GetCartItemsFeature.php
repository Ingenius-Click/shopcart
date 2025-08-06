<?php

namespace Ingenius\ShopCart\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class GetCartItemsFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'get-cart-items';
    }

    public function getName(): string
    {
        return __('Get cart items');
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
