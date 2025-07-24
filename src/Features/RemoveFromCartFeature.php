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
        return 'Remove from cart';
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
