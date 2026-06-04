<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id('patient_id');

            $table->string('patient_code', 20)->unique();
            $table->string('full_name', 50);

            $table->string('id_card', 20)->unique();

            $table->enum('sex', [
                'male',
                'female',
                'other'
            ]);

            $table->date('date_of_birth');
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();

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
        Schema::dropIfExists('patients');
    }
}
