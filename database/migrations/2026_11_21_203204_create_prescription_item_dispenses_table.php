<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionItemDispensesTable extends Migration
{
    public function up()
    {
        Schema::create('prescription_item_dispenses', function (Blueprint $table) {
            $table->id('dispense_id');
            $table->unsignedBigInteger('prescription_item_id');
            $table->unsignedInteger('quantity');          // actual quantity dispensed in this event
            $table->unsignedBigInteger('dispensed_by')->nullable();
            $table->timestamp('dispensed_at')->useCurrent();
            $table->timestamps();

            $table->index('prescription_item_id');
            $table->foreign('prescription_item_id')->references('prescription_item_id')->on('prescription_items');
            $table->foreign('dispensed_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('prescription_item_dispenses');
    }
}
