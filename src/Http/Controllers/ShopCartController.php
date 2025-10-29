<?php

namespace Ingenius\ShopCart\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Ingenius\Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ingenius\ShopCart\Actions\AddCartItem;
use Ingenius\ShopCart\Actions\DeleteCartItem;
use Ingenius\ShopCart\Actions\RemoveCartItem;
use Ingenius\ShopCart\Exceptions\InsufficientStockException;
use Ingenius\ShopCart\Http\Requests\AddCartItemRequest;
use Ingenius\ShopCart\Http\Requests\DeleteCartItemRequest;
use Ingenius\ShopCart\Http\Requests\RemoveCartItemRequest;
use Ingenius\ShopCart\Services\ShopCart;

class ShopCartController extends Controller
{

    /**
     * Add a product to the cart
     *
     * @param AddCartItemRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCartItem(AddCartItemRequest $request, AddCartItem $action)
    {
        // Request is already validated via AddCartItemRequest
        $validated = $request->validated();

        // Get the product ID and quantity
        $productId = $validated['product_id'];
        $quantity = $validated['quantity'];

        try {
            // Add to cart using the action
            $cartItem = $action->addProduct($productId, $quantity);

            if (!$cartItem) {
                return response()->json([
                    'message' => 'Product not found or invalid configuration',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'message' => 'Product added to cart',
                'data' => $cartItem
            ], 200);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    public function getShopCart(ShopCart $shopCart)
    {
        return response()->json([
            'message' => 'Shop cart retrieved successfully',
            'data' => $shopCart->toArray()
        ], 200);
    }

    public function smallShopCart(ShopCart $shopCart): JsonResponse
    {
        return response()->json([
            'message' => 'Small shop cart retrieved successfully',
            'data' => [
                'total_items' => $shopCart->getCartItems()->count(),
                'total_price' => $shopCart->calculateTotal(),
            ]
        ], 200);
    }

    /**
     * Get all cart items for the current user/session
     *
     * @param ShopCart $shopCart
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCartItems(ShopCart $shopCart)
    {
        // Get all cart items using the ShopCart service
        $cartItems = $shopCart->getCartItems();

        return response()->json([
            'message' => 'Cart items retrieved successfully',
            'data' => $cartItems
        ], 200);
    }

    /**
     * Remove a product from the cart
     *
     * @param RemoveCartItemRequest $request
     * @param RemoveCartItem $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCartItem(RemoveCartItemRequest $request, RemoveCartItem $action)
    {
        // Request is already validated via RemoveCartItemRequest
        $validated = $request->validated();

        // Get the product ID and quantity
        $productId = $validated['product_id'];
        $quantity = $validated['quantity'];

        // Remove from cart using the action
        $cartItem = $action->removeProduct($productId, $quantity);

        if ($cartItem === null) {
            return response()->json([
                'message' => 'Product removed from cart',
                'data' => null
            ], 200);
        }

        return response()->json([
            'message' => 'Product quantity updated in cart',
            'data' => $cartItem
        ], 200);
    }

    /**
     * Delete a cart item completely
     *
     * @param DeleteCartItemRequest $request
     * @param DeleteCartItem $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCartItem(DeleteCartItemRequest $request, DeleteCartItem $action)
    {
        // Request is already validated via DeleteCartItemRequest
        $validated = $request->validated();

        // Get the product ID
        $productId = $validated['product_id'];

        // Delete from cart using the action
        $success = $action->deleteProduct($productId);

        if (!$success) {
            return response()->json([
                'message' => 'Cart item not found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'message' => 'Cart item deleted successfully',
            'data' => null
        ], 200);
    }
}
