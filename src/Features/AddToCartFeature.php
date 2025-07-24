<?php

namespace Ingenius\ShopCart\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class AddToCartFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'add-to-cart';
    }

    public function getName(): string
    {
        return 'Add to cart';
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
