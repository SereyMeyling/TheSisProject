<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMedicineStockMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medicine_stock_movements', function (Blueprint $table) {
            $table->id('movement_id');

            $table->foreignId('batch_id')
                ->constrained('medicine_batches', 'batch_id');

            $table->enum('movement_type', [
                'IN',
                'OUT'
            ]);

            $table->unsignedInteger('quantity');

            $table->enum('reference_type', [
                'PURCHASE',
                'PRESCRIPTION',
                'ADJUSTMENT'
            ]);

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->dateTime('movement_date');

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
        Schema::dropIfExists('medicine_stock_movements');
    }
}
