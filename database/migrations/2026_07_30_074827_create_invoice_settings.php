<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('currency_symbol', 10)->default('$');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->string('invoice_prefix', 20)->nullable();
            $table->string('invoice_footer', 255)->nullable();
            $table->boolean('invoice_auto_number')->default(true);
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->enum('print_size', ['A4', '80mm'])->default('A4');
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
        Schema::dropIfExists('invoice_settings');
    }
}
