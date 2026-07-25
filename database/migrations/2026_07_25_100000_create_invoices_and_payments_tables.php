<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesAndPaymentsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Ensure patients table exists
        if (!Schema::hasTable('patients')) {
            Schema::create('patients', function (Blueprint $table) {
                $table->id('patient_id');
                $table->string('patient_code', 20)->unique();
                $table->string('full_name', 100);
                $table->string('id_card', 30)->nullable();
                $table->enum('sex', ['male', 'female', 'other'])->default('male');
                $table->date('date_of_birth')->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('address', 255)->nullable();
                $table->timestamps();
            });
        }

        // Invoices table
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number', 50)->unique();
                $table->unsignedBigInteger('patient_id')->nullable();
                $table->string('patient_name', 100);
                $table->string('patient_phone', 30)->nullable();
                $table->decimal('total_amount', 12, 2)->default(0.00);
                $table->decimal('paid_amount', 12, 2)->default(0.00);
                $table->decimal('balance', 12, 2)->default(0.00);
                $table->enum('status', ['unpaid', 'paid', 'partial'])->default('unpaid');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // Invoice Items table
        if (!Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
                $table->enum('item_type', ['consultation', 'prescription', 'lab_test', 'room', 'other'])->default('consultation');
                $table->string('description', 255);
                $table->integer('qty')->default(1);
                $table->decimal('unit_price', 12, 2)->default(0.00);
                $table->decimal('subtotal', 12, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // Invoice Payments table
        if (!Schema::hasTable('invoice_payments')) {
            Schema::create('invoice_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
                $table->decimal('amount', 12, 2);
                $table->enum('payment_method', ['cash', 'khqr', 'card'])->default('cash');
                $table->string('transaction_ref', 100)->nullable();
                $table->timestamp('paid_at')->useCurrent();
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
}
