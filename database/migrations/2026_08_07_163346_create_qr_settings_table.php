<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('qr_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('account_type', ['individual', 'merchant'])->default('individual');
            $table->string('bank_name'); // ABA, ACLEDA, WING, TRUE MONEY, BAKONG...
            $table->string('bakong_account_id'); // e.g. jonhsmith@nbcq — required for KHQR
            $table->string('account_name');
            $table->string('account_number')->nullable(); // for display only, not part of KHQR
            $table->string('merchant_city')->default('Phnom Penh');
            $table->string('merchant_id')->nullable(); // required if account_type = merchant
            $table->string('mobile_number')->nullable();
            $table->string('merchant_category_code')->default('5999');
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('qr_settings');
    }
};