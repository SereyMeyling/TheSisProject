<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToMedicinesTable extends Migration
{
    public function up()
    {
        Schema::table('medicines', function (Blueprint $table) {
            // National Drug Code / internal code
            $table->string('ndc_code', 50)->nullable()->after('medicine_name');

            // ថ្នាំគ្រាប់ / ថ្នាំទឹក / ថ្នាំចាក់ / ថ្នាំសម្រាប់លាប / ថ្នាំបំបាត់ការឈឺចាប់
            $table->string('category', 50)->nullable()->after('ndc_code');

            // mg / ml
            $table->string('dosage_unit', 10)->nullable()->after('category');

            // e.g. "500" -> combined with dosage_unit shown as "500mg"
            $table->string('strength', 30)->nullable()->after('dosage_unit');

            // How many sellable pieces (គ្រាប់) are in ONE purchase "unit"
            // e.g. medicines.unit = "box", pieces_per_unit = 100 -> 1 box = 100 tablets
            $table->unsignedInteger('pieces_per_unit')->default(1)->after('unit_price');

            // Selling price PER PIECE (គ្រាប់) - separate from purchase unit_price
            $table->decimal('selling_price', 12, 2)->unsigned()->default(0)->after('pieces_per_unit');

            // Alert threshold for the "ស្តុកជិតអស់" dashboard card, counted in pieces
            $table->unsignedInteger('reorder_level')->default(20)->after('selling_price');

            $table->boolean('is_active')->default(true)->after('reorder_level');
            $table->softDeletes();

            $table->index('medicine_name');
            $table->index('category');
        });
    }

    public function down()
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['medicine_name']);
            $table->dropIndex(['category']);
            $table->dropColumn([
                'ndc_code', 'category', 'dosage_unit', 'strength',
                'pieces_per_unit', 'selling_price', 'reorder_level', 'is_active',
            ]);
        });
    }
}
