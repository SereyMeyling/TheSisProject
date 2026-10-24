<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageDataToGeneralSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->longText('logo_data')->nullable();
            $table->string('logo_mime', 100)->nullable();
            $table->longText('favicon_data')->nullable();
            $table->string('favicon_mime', 100)->nullable();
        });
    }

    public function down()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['logo_data', 'logo_mime', 'favicon_data', 'favicon_mime']);
        });
    }
}
