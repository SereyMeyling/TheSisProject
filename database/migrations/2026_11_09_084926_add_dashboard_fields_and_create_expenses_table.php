<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
/**
 * Admin dashboard phase 1
 *  - medical_records.visit_type      : new | mf | bf | fu | refill | discharge   (nullable)
 *  - medical_records.diagnosis_group : DMII | HTN | RA | ... | OTHER             (nullable)
 *  - expenses                        : NON-medicine expenses (doctor fee, Echo, supplies, ...)
 *
 * Medicine purchases keep coming from medicine_batches (purchase_price * quantity_initial),
 * so do NOT enter them again in expenses or they will be counted twice.
 * Existing rows are untouched (new columns are NULL).
 */
class AddDashboardFieldsAndCreateExpensesTable extends Migration
{

    public function up()
    {
        // Add dashboard fields only if they don't exist
        if (!Schema::hasColumn('medical_records', 'visit_type')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->string('visit_type', 20)
                    ->nullable()
                    ->after('visit_date');
            });
        }

        if (!Schema::hasColumn('medical_records', 'diagnosis_group')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->string('diagnosis_group', 30)
                    ->nullable()
                    ->after('diagnosis');
            });
        }

        // Add composite index only if it doesn't exist
        $indexExists = DB::select("
    SHOW INDEX FROM medical_records
    WHERE Key_name = ?
", ['medical_records_visit_date_diagnosis_group_index']);

        if (empty($indexExists)) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->index(
                    ['visit_date', 'diagnosis_group'],
                    'medical_records_visit_date_diagnosis_group_index'
                );
            });
        }

        // Create expenses only if it doesn't exist
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id('expense_id');
                $table->date('expense_date');
                $table->string('category', 20)->default('other');
                $table->string('description', 255);
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('amount', 12, 2);
                $table->enum('payment_method', ['cash', 'bank'])
                    ->default('cash');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('expense_date');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('expenses');

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropIndex(['visit_date', 'diagnosis_group']);
            $table->dropColumn(['visit_type', 'diagnosis_group']);
        });
    }
}
