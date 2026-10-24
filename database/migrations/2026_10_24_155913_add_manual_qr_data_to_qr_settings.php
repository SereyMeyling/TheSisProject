<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManualQrDataToQrSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qr_settings', function (Blueprint $table) {
            $table->longText('manual_qr_data')->nullable();
            $table->string('manual_qr_mime', 100)->nullable();
        });
    }

    public function down()
    {
        Schema::table('qr_settings', function (Blueprint $table) {
            $table->dropColumn(['manual_qr_data', 'manual_qr_mime']);
        });
    }
}
