<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_items', function (Blueprint $table) {
            $table->id('billing_item_id');

            $table->foreignId('billing_id')
                ->constrained('billings', 'billing_id')
                ->cascadeOnDelete();

            $table->enum('item_type', [
                'room',
                'medicine',
                'service'
            ]);

            $table->string('description', 255);

            $table->unsignedInteger('quantity');

            $table->decimal('unit_price', 12, 2)->unsigned();
            $table->decimal('amount', 12, 2)->unsigned();

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
        Schema::dropIfExists('billing_items');
    }
}
