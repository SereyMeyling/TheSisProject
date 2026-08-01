<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleItemsTable extends Migration
{
    public function up()
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id('sale_item_id');

            $table->foreignId('sale_id')
                ->constrained('sales', 'sale_id')
                ->cascadeOnDelete();

            $table->foreignId('medicine_id')
                ->constrained('medicines', 'medicine_id');

            // Which batch this line was drawn from (FIFO). A single "sell 30 pieces"
            // request can produce more than one sale_items row if it has to
            // span two batches.
            $table->foreignId('batch_id')
                ->constrained('medicine_batches', 'batch_id');

            $table->unsignedInteger('quantity'); // in pieces / គ្រាប់
            $table->decimal('unit_price', 12, 2)->unsigned();
            $table->decimal('subtotal', 12, 2)->unsigned();

            $table->timestamps();

            $table->index(['sale_id', 'medicine_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sale_items');
    }
}
