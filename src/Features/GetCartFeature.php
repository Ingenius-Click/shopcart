<?php

namespace Ingenius\ShopCart\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class GetCartFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'get-cart';
    }

    public function getName(): string
    {
        return 'Get cart';
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
