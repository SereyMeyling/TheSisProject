<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientChargesTable extends Migration
{
    public function up()
    {
        Schema::create('patient_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('medical_record_id')->nullable();
            $table->unsignedBigInteger('admission_id')->nullable();

            $table->string('source_type', 20);            // medicine | lab | room
            $table->unsignedBigInteger('source_id')->nullable(); // dispense_id | result_id | admission_id
            $table->string('source_key', 150)->unique();  // e.g. room:12:2026-11-20

            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);         // snapshot at creation
            $table->decimal('total_price', 12, 2);
            $table->date('charge_date');

            $table->string('status', 20)->default('pending'); // pending | invoiced | cancelled
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->timestamp('invoiced_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index('invoice_id');

            $table->foreign('patient_id')->references('patient_id')->on('patients');
            $table->foreign('medical_record_id')->references('record_id')->on('medical_records');
            $table->foreign('admission_id')->references('admission_id')->on('admissions');
            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('cancelled_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('patient_charges');
    }
}
