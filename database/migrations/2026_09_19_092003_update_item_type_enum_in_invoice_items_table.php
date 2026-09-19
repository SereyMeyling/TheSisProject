
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE invoice_items
            MODIFY item_type ENUM(
                'service',
                'room',
                'medicine',
                'lab_test',
                'consultation',
                'prescription',
                'other'
            ) NOT NULL DEFAULT 'other'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE invoice_items
            MODIFY item_type ENUM(
                'consultation',
                'prescription',
                'lab_test',
                'room',
                'other'
            ) NOT NULL DEFAULT 'consultation'
        ");
    }
};
