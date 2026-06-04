<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id('employee_id');

            $table->foreignId('department_id')
                ->constrained('departments', 'department_id')
                ->cascadeOnDelete();

            $table->string('employee_code', 20)->unique();
            $table->string('first_name', 50);
            $table->string('last_name', 50);

            $table->enum('role', [
                'doctor',
                'nurse',
                'pharmacist',
                'lab_technician',
                'admin'
            ]);

            $table->string('specialization', 100)->nullable();
            $table->string('phone', 20)->nullable();

            $table->enum('status', [
                'active',
                'inactive'
            ])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
