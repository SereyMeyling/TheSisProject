<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id('result_id');

            $table->foreignId('lab_order_id')
                ->constrained('lab_orders', 'lab_order_id');

            $table->foreignId('test_id')
                ->constrained('lab_tests', 'test_id');

            $table->string('result_value', 100);
            $table->string('normal_range', 100)->nullable();
            $table->string('remark', 255)->nullable();

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
        Schema::dropIfExists('lab_results');
    }
}
