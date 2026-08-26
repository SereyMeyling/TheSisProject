<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModeToQrSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qr_settings', function (Blueprint $table) {
            $table->enum('mode', ['manual', 'bakong'])->default('manual')->after('id');
            $table->string('manual_qr_image')->nullable()->after('mode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qr_settings', function (Blueprint $table) {
            $table->dropColumn(['mode', 'manual_qr_image']);
        });
    }
}
