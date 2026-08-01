<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSaleToStockMovementReferenceType extends Migration
{
    public function up()
    {
        // Enum change needs raw SQL (no doctrine/dbal round-trip needed this way)
        DB::statement("ALTER TABLE medicine_stock_movements
            MODIFY reference_type ENUM('PURCHASE','PRESCRIPTION','ADJUSTMENT','SALE') NOT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE medicine_stock_movements
            MODIFY reference_type ENUM('PURCHASE','PRESCRIPTION','ADJUSTMENT') NOT NULL");
    }
}
