<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');

            $table->foreignId('billing_id')
                ->constrained('billings', 'billing_id');

            $table->dateTime('payment_date');

            $table->decimal('amount', 12, 2);

            $table->enum('method', [
                'cash',
                'card',
                'online'
            ]);

            $table->enum('status', [
                'success',
                'failed',
                'pending'
            ]);

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
}
