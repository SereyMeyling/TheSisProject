<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddColumnsToMedicineBatchesTable extends Migration
{
    public function up()
    {
        Schema::table('medicine_batches', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->after('medicine_id')
                ->constrained('suppliers', 'supplier_id')
                ->nullOnDelete();

            // Live remaining stock for this batch, in pieces (គ្រាប់).
            // Kept as a real column (updated on every sale/purchase) instead of
            // SUM()-ing medicine_stock_movements every page load - this is the
            // single biggest thing that keeps the pharmacy screen fast at scale.
            $table->unsignedInteger('remaining_quantity')->default(0)->after('quantity_initial');

            // Composite index: this is exactly the query FIFO selling and the
            // "near expiry" dashboard card run (medicine_id + soonest expiry first)
            $table->index(['medicine_id', 'expiry_date']);
            $table->index('expiry_date');
        });

        // Backfill remaining_quantity = quantity_initial for any existing rows
        DB::table('medicine_batches')
            ->update(['remaining_quantity' => DB::raw('quantity_initial')]);
    }

    public function down()
    {
        Schema::table('medicine_batches', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['medicine_id', 'expiry_date']);
            $table->dropIndex(['expiry_date']);
            $table->dropColumn(['supplier_id', 'remaining_quantity']);
        });
    }
}
