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
       Schema::create('sale_returns', function (Blueprint $table) {
    $table->id();

    $table->foreignId('sale_id')
        ->constrained('sales')
        ->cascadeOnDelete();

    $table->dateTime('return_date');

    $table->decimal('refund_amount', 10, 2)->default(0);

    $table->string('refund_method')->nullable(); // cash / upi / card
    $table->string('reason')->nullable();

    $table->foreignId('created_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

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
        Schema::dropIfExists('sale_returns');
    }
};
