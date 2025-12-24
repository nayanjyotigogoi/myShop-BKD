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
         Schema::table('payments', function (Blueprint $table) {
            $table
                ->foreignId('sale_return_id')
                ->nullable()
                ->after('sale_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['sale_return_id']);
            $table->dropColumn('sale_return_id');
        });
    }
};
