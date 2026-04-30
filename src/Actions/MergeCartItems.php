<?php

namespace Ingenius\ShopCart\Actions;

use Ingenius\ShopCart\Models\CartItem;

class MergeCartItems {

    public function handle(string $guestToken, int $userId, string $userClass) {
        $guestCartItems = CartItem::where('guest_token', $guestToken)->where('owner_id', null)->get();

        $userCartItems = CartItem::where('owner_id', $userId)->where('owner_type', $userClass)->get();

        $cartItemsToDelete = [];

        foreach ($guestCartItems as $guestCartItem) {

            if($cartItem = 
                $userCartItems
                    ->where('productible_id', $guestCartItem->productible_id)
                    ->where('productible_type', $guestCartItem->productible_type)
                    ->first()
                )
            {
                if($cartItem->quantity < $guestCartItem->quantity) {
                    $cartItem->quantity = $guestCartItem->quantity;
                    $cartItem->save();
                }

                $cartItemsToDelete[] = $guestCartItem->id;
            } else {
                $guestCartItem->owner_id = $userId;
                $guestCartItem->owner_type = $userClass;
                $guestCartItem->guest_token = null;

                $guestCartItem->save();
            }

        }

        if(count($cartItemsToDelete) > 0) {
            CartItem::whereIn('id', $cartItemsToDelete)->delete();
        }
    }

}