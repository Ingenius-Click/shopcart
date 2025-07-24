# Ingenius ShopCart

A Laravel package for implementing shopping cart functionality with modifiers support.

## Installation

```bash
composer require ingenius/shopcart
```

## Features

- Add products to cart (supports polymorphic relationships)
- Remove products from cart
- Delete cart items
- Get cart items
- Cart modifiers system for extending functionality
- Session-based cart for guests
- User-based cart for authenticated users

## Configuration

### Environment Variables

```
PRODUCT_MODEL=Ingenius\Products\Models\Product
```

> Note: For backward compatibility, `SHOPCART_PRODUCT_MODEL` is still supported but `PRODUCT_MODEL` is preferred as it's used across all packages.

## ShopCart Modifier System

The ShopCart package includes a modifier system that allows other packages to extend the cart's functionality without the ShopCart package needing to know about them.

### Features

- Add custom calculations to the cart total (shipping, discounts, taxes, etc.)
- Extend the cart's JSON/array output with additional data
- Control the execution order of modifiers using priorities
- Easy to implement in other packages

### How It Works

The ShopCart uses a pipeline approach with modifiers that:

1. Calculate subtotals in priority order (lower numbers run first)
2. Extend the `toArray()` output with custom values from each modifier
3. Allow precise control over execution order

### Creating Your Own Modifier

To create a new cart modifier in your package:

1. Create a class that extends `BaseCartModifier` or implements `CartModifierInterface`
2. Set its priority to control when it runs (lower = earlier)
3. Register it with the `CartModifierManager` in your package's service provider

#### Example Modifier

```php
<?php

namespace YourPackage\CartModifiers;

use Ingenius\ShopCart\Modifiers\BaseCartModifier;
use Ingenius\ShopCart\Services\ShopCart;

class YourModifier extends BaseCartModifier
{
    /**
     * Set priority - lower numbers run first
     */
    public function getPriority(): int
    {
        return 50; // Run after the base item subtotal calculations
    }
    
    /**
     * Modify the subtotal
     */
    public function calculateSubtotal(ShopCart $cart, float $currentSubtotal): float
    {
        // Your custom calculation logic here
        $yourModification = 10.00;
        
        // Store data for use in extendCartArray
        $this->yourData = $yourModification;
        
        // Return modified subtotal
        return $currentSubtotal + $yourModification;
    }
    
    /**
     * Add data to the cart array
     */
    public function extendCartArray(ShopCart $cart, array $cartArray): array
    {
        $cartArray['your_data'] = [
            'amount' => $this->yourData,
            'other_info' => 'Custom information'
        ];
        
        return $cartArray;
    }
    
    protected $yourData;
}
```

### Registering Your Modifier

In your package's service provider:

```php
<?php

namespace YourPackage\Providers;

use Illuminate\Support\ServiceProvider;
use Ingenius\ShopCart\Services\CartModifierManager;
use YourPackage\CartModifiers\YourModifier;

class YourPackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register your cart modifier
        $this->app->afterResolving(CartModifierManager::class, function (CartModifierManager $manager) {
            $manager->register(new YourModifier());
        });
    }
}
```

### Command Line Tools

The ShopCart package includes command line tools to help you manage and debug cart modifiers.

#### Listing Registered Cart Modifiers

To view all registered cart modifiers and their priorities, use the following command:

```bash
php artisan shopcart:modifiers
```

## Usage

```php
// Usage examples will go here
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.