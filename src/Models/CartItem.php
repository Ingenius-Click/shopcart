<?php

namespace Ingenius\ShopCart\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'guest_token',
        'productible_id',
        'productible_type',
        'quantity',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Scope to filter only non-expired cart items.
     * Items with null expires_at are considered non-expired (legacy items).
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('expires_at', '>', now())
              ->orWhereNull('expires_at');
        });
    }

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
