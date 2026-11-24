<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The `expenses` table may have been created by either of two migrations:
 *   - 2026_09_09_183548 (enum category salary/supply/..., no quantity/payment_method)
 *   - 2026_11_09_084926 (string category, quantity, payment_method)
 * The second one is skipped when the table already exists, so the Expense model
 * and dashboard export reference columns that may be missing. This migration
 * brings both variants to the same shape.
 */
class AlignExpensesTableWithModel extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('expenses')) {
            return;
        }

        // enum -> string so the model's categories (doctor, service, supplies, ...) are accepted
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE expenses MODIFY category VARCHAR(20) NOT NULL DEFAULT 'other'");
        }

        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('description');
            }
            if (!Schema::hasColumn('expenses', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'bank'])->default('cash')->after('amount');
            }
        });
    }

    public function down()
    {
        // Non-destructive: keep the columns (removing them would lose data).
    }
}
