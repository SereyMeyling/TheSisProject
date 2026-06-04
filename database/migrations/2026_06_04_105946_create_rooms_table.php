<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id('room_id');

            $table->string('room_number', 20)->unique();

            $table->enum('room_type', [
                'general',
                'private',
                'icu',
                'isolation'
            ]);

            $table->enum('status', [
                'available',
                'occupied',
                'maintenance'
            ]);

            $table->decimal('price_per_day', 12, 2)->unsigned();

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
        Schema::dropIfExists('rooms');
    }
}
