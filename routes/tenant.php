<?php

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here is where you can register tenant-specific routes for your module.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Ingenius\ShopCart\Http\Controllers\ShopCartController;

Route::middleware([
    'api',
])->prefix('api')->group(function () {
    // ShopCart routes
    Route::post('/cart/product/add', [ShopCartController::class, 'addCartItem'])
        ->name('shopcart.add.product')
        ->middleware('tenant.has.feature:add-to-cart');
    Route::get('/cart/items', [ShopCartController::class, 'getCartItems'])
        ->name('shopcart.get.items')
        ->middleware('tenant.has.feature:get-cart-items');
    Route::get('/cart', [ShopCartController::class, 'getShopCart'])
        ->name('shopcart.get.cart')
        ->middleware('tenant.has.feature:get-cart');
    Route::get('/small-cart', [ShopCartController::class, 'smallShopCart'])
        ->name('shopcart.get.cart.small')
        ->middleware('tenant.has.feature:get-cart');
    Route::put('/cart/product/remove', [ShopCartController::class, 'removeCartItem'])
        ->name('shopcart.remove.product')
        ->middleware('tenant.has.feature:remove-from-cart');
    Route::delete('/cart/product/delete', [ShopCartController::class, 'deleteCartItem'])
        ->name('shopcart.delete.product')
        ->middleware('tenant.has.feature:delete-from-cart');
});

// Route::get('tenant-example', function () {
//     return 'Hello from tenant-specific route! Current tenant: ' . tenant('id');
// });