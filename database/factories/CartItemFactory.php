<?php

namespace Ingenius\ShopCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;
use Ingenius\ShopCart\Models\CartItem;

class CartItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $productClass = Config::get('shopcart.product_model');

        $product = $productClass::factory()->create();

        return [
            'owner_id' => null,
            'owner_type' => null,
            'session_id' => $this->faker->uuid(),
            'productible_id' => $product->id,
            'productible_type' => $productClass,
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Configure the factory to set the owner to a specific model.
     */
    public function forOwner($owner): self
    {
        return $this->state(function (array $attributes) use ($owner) {
            return [
                'owner_id' => $owner->id,
                'owner_type' => get_class($owner),
                'session_id' => null,
            ];
        });
    }

    /**
     * Configure the factory to set the product to a specific model.
     */
    public function forProduct($product): self
    {
        return $this->state(function (array $attributes) use ($product) {
            return [
                'productible_id' => $product->id,
                'productible_type' => get_class($product),
            ];
        });
    }
}
