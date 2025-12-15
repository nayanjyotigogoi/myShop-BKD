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
        Schema::create('sale_return_items', function (Blueprint $table) {
    $table->id();

    $table->foreignId('sale_return_id')
        ->constrained('sale_returns')
        ->cascadeOnDelete();

    $table->foreignId('sale_item_id')
        ->constrained('sale_items')
        ->cascadeOnDelete();

    $table->foreignId('product_id')
        ->constrained('products');

    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);
    $table->decimal('line_total', 10, 2);

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
        Schema::dropIfExists('sale_return_items');
    }
};
