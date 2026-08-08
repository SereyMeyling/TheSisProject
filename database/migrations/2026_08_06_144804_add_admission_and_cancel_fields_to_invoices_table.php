<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds OPD/IPD support and safe (non-destructive) cancellation support to invoices.
 *
 * - admission_id: NULL => OPD invoice, filled => IPD invoice tied to an admission/room.
 * - cancelled_by / cancelled_at / cancel_reason: audit trail for cancelled invoices.
 *   We NEVER hard-delete invoices; cancellation is a status change only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // --- OPD / IPD linkage ---
            $table->unsignedBigInteger('admission_id')->nullable()->after('patient_id');

            $table->foreign('admission_id')
                ->references('admission_id')->on('admissions')
                ->nullOnDelete();

            $table->index('admission_id');

            // --- Cancellation audit trail ---
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('created_by');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->string('cancel_reason', 500)->nullable()->after('cancelled_at');

            $table->foreign('cancelled_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        // NOTE: your `status` column is validated at the app level (see
        // ProcessPaymentRequest / BillingController), not as a native MySQL
        // ENUM, so 'cancelled' just works as a new string value — no
        // schema change needed here. If your `status` column IS a native
        // ENUM in your DB, uncomment and adjust:
        //
        // DB::statement("ALTER TABLE invoices MODIFY status
        //     ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['admission_id']);
            $table->dropForeign(['cancelled_by']);
            $table->dropIndex(['admission_id']);

            $table->dropColumn([
                'admission_id',
                'cancelled_by',
                'cancelled_at',
                'cancel_reason',
            ]);
        });
    }
};
