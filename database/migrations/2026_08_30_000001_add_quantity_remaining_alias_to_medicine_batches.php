<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddQuantityRemainingAliasToMedicineBatches extends Migration
{
    public function up()
    {
        if (Schema::hasTable('medicine_batches') && !Schema::hasColumn('medicine_batches', 'quantity_remaining')) {
            Schema::table('medicine_batches', function (Blueprint $table) {
                $table->unsignedInteger('quantity_remaining')->default(0)->after('quantity_initial');
            });

            // Backfill quantity_remaining from remaining_quantity if present, or quantity_initial
            if (Schema::hasColumn('medicine_batches', 'remaining_quantity')) {
                DB::table('medicine_batches')->update(['quantity_remaining' => DB::raw('remaining_quantity')]);
            } else {
                DB::table('medicine_batches')->update(['quantity_remaining' => DB::raw('quantity_initial')]);
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('medicine_batches') && Schema::hasColumn('medicine_batches', 'quantity_remaining')) {
            Schema::table('medicine_batches', function (Blueprint $table) {
                $table->dropColumn('quantity_remaining');
            });
        }
    }
}
