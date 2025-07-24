<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can specify configuration options for the shopcart package.
    |
    */

    'name' => 'ShopCart',

    /*
    |--------------------------------------------------------------------------
    | Product Model Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can specify which class to use as the Product model when
    | adding items to the cart. This allows flexibility to change the
    | product model implementation without modifying the cart logic.
    |
    */
    'product_model' => env('PRODUCT_MODEL', env('SHOPCART_PRODUCT_MODEL', 'Ingenius\Products\Models\Product')),
];
