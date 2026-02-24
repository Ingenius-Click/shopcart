<?php

namespace Ingenius\ShopCart\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Ingenius\Core\Services\FeatureManager;
use Ingenius\Core\Services\PackageHookManager;
use Ingenius\Core\Services\ScheduledTaskManager;
use Ingenius\Core\Traits\RegistersConfigurations;
use Ingenius\Core\Traits\RegistersMigrations;
use Ingenius\ShopCart\Console\Tasks\ClearExpiredCartItemsTask;
use Ingenius\ShopCart\Features\AddToCartFeature;
use Ingenius\ShopCart\Features\DeleteFromCartFeature;
use Ingenius\ShopCart\Features\GetCartFeature;
use Ingenius\ShopCart\Features\GetCartItemsFeature;
use Ingenius\ShopCart\Features\RemoveFromCartFeature;
use Ingenius\ShopCart\Models\CartItem;
use Ingenius\ShopCart\Services\ShopCart;
use Ingenius\ShopCart\Services\CartModifierManager;
use Ingenius\ShopCart\Console\ListCartModifiersCommand;

class ShopCartServiceProvider extends ServiceProvider
{
    use RegistersMigrations, RegistersConfigurations;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/shopcart.php', 'shopcart');

        // Register configuration with the registry
        $this->registerConfig(__DIR__ . '/../../config/shopcart.php', 'shopcart', 'shopcart');

        // Register the route service provider
        $this->app->register(RouteServiceProvider::class);

        // Register the CartModifierManager as a singleton
        $this->app->singleton(CartModifierManager::class, function ($app) {
            return new CartModifierManager();
        });

        // Register ShopCart with the CartModifierManager injected
        $this->app->singleton(ShopCart::class, function ($app) {
            return new ShopCart($app->make(CartModifierManager::class));
        });

        $this->app->afterResolving(FeatureManager::class, function (FeatureManager $manager) {
            $manager->register(new AddToCartFeature());
            $manager->register(new GetCartItemsFeature());
            $manager->register(new GetCartFeature());
            $manager->register(new RemoveFromCartFeature());
            $manager->register(new DeleteFromCartFeature());
        });

        // Register stock reservation hook
        $this->registerStockReservationHook();

        // Register scheduled tasks
        $this->registerScheduledTasks();

        // Register user anonymization hooks
        $this->registerUserAnonymizationHooks();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register migrations with the registry
        $this->registerMigrations(__DIR__ . '/../../database/migrations', 'shopcart');

        // Check if there's a tenant migrations directory and register it
        $tenantMigrationsPath = __DIR__ . '/../../database/migrations/tenant';
        if (is_dir($tenantMigrationsPath)) {
            $this->registerTenantMigrations($tenantMigrationsPath, 'shopcart');
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/shopcart.php' => config_path('shopcart.php'),
        ], 'shopcart-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'shopcart-migrations');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ListCartModifiersCommand::class,
            ]);
        }
    }

    /**
     * Register hook to provide cart reservation counts for stock availability
     */
    protected function registerStockReservationHook(): void
    {
        $this->app->afterResolving(PackageHookManager::class, function (PackageHookManager $manager) {
            $manager->register('stock.reservations.get', function ($reservedSoFar, $context) {
                $reserved = CartItem::query()
                    ->where('productible_id', $context['productible_id'])
                    ->where('productible_type', $context['productible_type'])
                    ->notExpired()
                    ->sum('quantity');

                return $reservedSoFar + $reserved;
            }, 10);
        });
    }

    /**
     * Register scheduled tasks for the shopcart package
     */
    protected function registerScheduledTasks(): void
    {
        $this->app->afterResolving(ScheduledTaskManager::class, function (ScheduledTaskManager $manager) {
            $manager->register($this->app->make(ClearExpiredCartItemsTask::class));
        });
    }

    /**
     * Register hooks for user anonymization
     */
    protected function registerUserAnonymizationHooks(): void
    {
        $this->app->afterResolving(PackageHookManager::class, function (PackageHookManager $manager) {
            // Listen to user.before_anonymize hook to clean up cart items
            $manager->register('user.before_anonymize', function ($data, $context) {
                $userId = $context['user_id'] ?? null;
                $userClass = $context['user_class'] ?? null;

                if ($userId && $userClass) {
                    // Delete all cart items owned by this user (polymorphic relationship)
                    DB::table('cart_items')
                        ->where('owner_type', $userClass)
                        ->where('owner_id', $userId)
                        ->delete();
                }

                return $data;
            }, 10);
        });
    }
}
