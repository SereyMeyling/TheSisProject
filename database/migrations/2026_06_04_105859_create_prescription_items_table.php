<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id('prescription_item_id');

            $table->foreignId('prescription_id')
                ->constrained('prescriptions', 'prescription_id')
                ->cascadeOnDelete();

            $table->foreignId('medicine_id')
                ->constrained('medicines', 'medicine_id');

            $table->unsignedInteger('quantity');
            $table->string('dosage', 100);
            $table->string('frequency', 50);
            $table->unsignedInteger('duration_days');

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
        Schema::dropIfExists('prescription_items');
    }
}
