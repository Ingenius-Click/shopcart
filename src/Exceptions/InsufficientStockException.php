<?php

namespace Ingenius\ShopCart\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    protected $productId;
    protected $requestedQuantity;
    protected $availableStock;

    public function __construct(int $productId, float $requestedQuantity, ?float $availableStock)
    {
        $this->productId = $productId;
        $this->requestedQuantity = $requestedQuantity;
        $this->availableStock = $availableStock;

        $message = "Insufficient stock for product ID {$productId}. Requested: {$requestedQuantity}, Available: " .
            ($availableStock !== null ? $availableStock : 'None');

        parent::__construct($message, 400);
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getRequestedQuantity(): float
    {
        return $this->requestedQuantity;
    }

    public function getAvailableStock(): ?float
    {
        return $this->availableStock;
    }
}
