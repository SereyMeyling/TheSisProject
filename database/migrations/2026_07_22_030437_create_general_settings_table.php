<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();

            $table->string('system_name')->default('Hospital Management System');

            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->text('address')->nullable();

            $table->string('facebook')->nullable();
            $table->string('telegram')->nullable();

            $table->string('working_hours')->nullable();

            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();

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
        Schema::dropIfExists('general_settings');
    }
}
