<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class AddCashierRoleToEmployeesTable extends Migration
{
    public function up()
    {
        DB::statement("
            ALTER TABLE employees
            MODIFY role ENUM(
                'doctor',
                'nurse',
                'pharmacist',
                'lab_technician',
                'admin',
                'cashier'
            ) NOT NULL
        ");
    }

    public function down()
    {
        DB::statement("
            ALTER TABLE employees
            MODIFY role ENUM(
                'doctor',
                'nurse',
                'pharmacist',
                'lab_technician',
                'admin'
            ) NOT NULL
        ");
    }
}
