<?php

namespace Ingenius\ShopCart\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class DeleteFromCartFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'delete-from-cart';
    }

    public function getName(): string
    {
        return 'Delete from cart';
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
