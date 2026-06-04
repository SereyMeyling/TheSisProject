<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMedicineBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id('batch_id');

            $table->foreignId('medicine_id')
                ->constrained('medicines', 'medicine_id');

            $table->string('batch_number', 50)->unique();

            $table->date('expiry_date');

            $table->unsignedInteger('quantity_initial');

            $table->decimal('purchase_price', 12, 2)->unsigned();

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
        Schema::dropIfExists('medicine_batches');
    }
}
