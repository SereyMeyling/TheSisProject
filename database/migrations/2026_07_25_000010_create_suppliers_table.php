<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuppliersTable extends Migration
{
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('suppliers');
    }
}
