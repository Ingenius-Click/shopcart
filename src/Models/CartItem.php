<?php

namespace Ingenius\ShopCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Ingenius\ShopCart\Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CartItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'owner_id',
        'owner_type',
        'session_id',
        'productible_id',
        'productible_type',
        'quantity',
    ];

    /**
     * Get the productible model (Product or any other purchasable model)
     */
    public function productible(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the owner of this cart item (User or any other model)
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): CartItemFactory
    {
        return CartItemFactory::new();
    }
}
