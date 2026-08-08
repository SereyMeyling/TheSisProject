<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("
            ALTER TABLE invoices
            MODIFY status ENUM(
                'unpaid',
                'paid',
                'partial',
                'cancelled'
            ) DEFAULT 'unpaid'
        ");
    }

    public function down()
    {
        DB::statement("
            ALTER TABLE invoices
            MODIFY status ENUM(
                'unpaid',
                'paid',
                'partial'
            ) DEFAULT 'unpaid'
        ");
    }
};