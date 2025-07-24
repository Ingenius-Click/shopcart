<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            // Owner can be session or user
            $table->nullableMorphs('owner');

            // Session ID for guests
            $table->string('session_id')->nullable();

            // Product relationship (polymorphic)
            $table->morphs('productible');

            // Quantity
            $table->unsignedInteger('quantity')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
