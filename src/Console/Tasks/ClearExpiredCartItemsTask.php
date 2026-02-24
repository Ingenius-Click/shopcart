<?php

namespace Ingenius\ShopCart\Console\Tasks;

use Illuminate\Support\Facades\Log;
use Ingenius\Core\Interfaces\ScheduledTaskInterface;
use Ingenius\Core\Interfaces\StockAvailabilityInterface;
use Ingenius\ShopCart\Models\CartItem;

class ClearExpiredCartItemsTask implements ScheduledTaskInterface
{
    /**
     * Run every 15 minutes to clean up expired cart items
     */
    public function schedule(): string
    {
        return 'everyFifteenMinutes';
    }

    /**
     * Delete expired cart items and invalidate stock cache for affected products
     */
    public function handle(): void
    {
        $expiredQuery = CartItem::whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        // Collect distinct affected products before deletion so we can invalidate cache
        $affectedProducts = (clone $expiredQuery)
            ->select('productible_type', 'productible_id')
            ->distinct()
            ->get();

        $deleted = $expiredQuery->delete();

        if ($deleted > 0) {
            Log::info('ClearExpiredCartItemsTask: deleted ' . $deleted . ' expired cart items');

            if ($affectedProducts->isNotEmpty() && app()->bound(StockAvailabilityInterface::class)) {
                $stockService = app(StockAvailabilityInterface::class);

                foreach ($affectedProducts as $item) {
                    $stockService->invalidateCache($item->productible_type, $item->productible_id);
                }
            }
        }
    }

    /**
     * Human-readable description
     */
    public function description(): string
    {
        return 'Delete expired cart items to release reserved stock';
    }

    /**
     * This task should run per-tenant
     */
    public function isTenantAware(): bool
    {
        return true;
    }

    /**
     * Unique identifier for this task
     */
    public function getIdentifier(): string
    {
        return 'shopcart:clear-expired-cart-items';
    }
}
