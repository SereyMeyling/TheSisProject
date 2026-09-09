<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToLabTestsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('lab_tests')) {
            Schema::table('lab_tests', function (Blueprint $table) {
                if (!Schema::hasColumn('lab_tests', 'test_code')) {
                    $table->string('test_code', 50)->nullable()->after('test_name');
                }
                if (!Schema::hasColumn('lab_tests', 'normal_range')) {
                    $table->string('normal_range', 100)->nullable()->after('unit');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('lab_tests')) {
            Schema::table('lab_tests', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('lab_tests', 'test_code')) $columns[] = 'test_code';
                if (Schema::hasColumn('lab_tests', 'normal_range')) $columns[] = 'normal_range';
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
}
