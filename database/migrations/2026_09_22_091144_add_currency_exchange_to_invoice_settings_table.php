<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            $table->string('secondary_currency_symbol', 10)->nullable()->default('៛')->after('currency_symbol');
            $table->decimal('exchange_rate', 12, 2)->nullable()->default(4100)->after('secondary_currency_symbol');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            $table->dropColumn(['secondary_currency_symbol', 'exchange_rate']);
        });
    }
};