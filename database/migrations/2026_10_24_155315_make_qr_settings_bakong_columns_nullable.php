<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeQrSettingsBakongColumnsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE qr_settings MODIFY bakong_account_id VARCHAR(100) NULL');
        DB::statement('ALTER TABLE qr_settings MODIFY merchant_city VARCHAR(100) NULL');
        DB::statement('ALTER TABLE qr_settings MODIFY merchant_id VARCHAR(50) NULL');
        DB::statement('ALTER TABLE qr_settings MODIFY mobile_number VARCHAR(20) NULL');
        DB::statement('ALTER TABLE qr_settings MODIFY merchant_category_code VARCHAR(10) NULL');
        DB::statement('ALTER TABLE qr_settings MODIFY manual_qr_image VARCHAR(255) NULL');
        DB::statement('ALTER TABLE qr_settings MODIFY logo VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
