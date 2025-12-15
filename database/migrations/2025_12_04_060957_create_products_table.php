<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            $table->string('code')->unique(); // e.g., TS001
            $table->string('name');

            // Kids / Boys / Girls
            $table->string('category');

            // Unisex / Boys / Girls
            $table->string('gender')->nullable();

            // e.g., "4-6 years", "M", "L"
            $table->string('size')->nullable();

            $table->string('color')->nullable();

            // Default / last known buy & sell prices
            $table->decimal('buy_price', 10, 2)->default(0);
            $table->decimal('sell_price', 10, 2)->default(0);

            // Current stock (in pieces)
            $table->integer('current_stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
