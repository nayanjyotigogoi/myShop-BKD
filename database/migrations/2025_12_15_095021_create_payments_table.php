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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('sale_id')
              ->constrained()
              ->cascadeOnDelete();

        $table->foreignId('customer_id')
              ->nullable()
              ->constrained()
              ->nullOnDelete();

        $table->decimal('amount', 10, 2);
        $table->string('payment_method'); // cash, upi, card, bank
        $table->dateTime('payment_date');
        $table->string('reference_no')->nullable();

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
        Schema::dropIfExists('payments');
    }
};
