<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDoctorFieldsToMedicalRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medical_records', function (Blueprint $table) {
            if (!Schema::hasColumn('medical_records', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('patient_id');
            }

            if (!Schema::hasColumn('medical_records', 'status_destination')) {
                $table->string('status_destination')->nullable()->after('diagnosis');
            }

            if (!Schema::hasColumn('medical_records', 'prescription_notes')) {
                $table->text('prescription_notes')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id',
                'status_destination',
                'prescription_notes'
            ]);
        });
    }
}
