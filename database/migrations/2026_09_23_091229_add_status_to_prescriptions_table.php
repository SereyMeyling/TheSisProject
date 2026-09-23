<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('status')
                ->default('pending')
                ->after('prescribed_date');

            $table->timestamp('dispensed_at')
                ->nullable()
                ->after('status');

            $table->unsignedBigInteger('dispensed_by')
                ->nullable()
                ->after('dispensed_at');
        });
    }

    public function down()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'dispensed_at',
                'dispensed_by',
            ]);
        });
    }
};
