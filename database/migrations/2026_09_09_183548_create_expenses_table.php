<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensesTable extends Migration
{
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id('expense_id');

            $table->enum('category', [
                'salary',
                'supply',       // medicine/equipment purchase
                'utility',      // electricity, water, internet
                'maintenance',
                'other',
            ]);

            $table->string('description', 255);
            $table->decimal('amount', 12, 2)->unsigned();
            $table->date('expense_date');

            $table->foreignId('created_by')->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('expenses');
    }
}
