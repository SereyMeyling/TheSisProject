<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAvatarAndAddDepartmentToUsersTable extends Migration
{
    public function up()
    {
        // ចាស់ (string path) → លុបចោល
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });

        // ថ្មី (base64 blob ក្នុង DB) + department + specialization
        Schema::table('users', function (Blueprint $table) {
            $table->longText('avatar')->nullable()->after('phone');
            $table->string('avatar_mime', 100)->nullable()->after('avatar');

            $table->foreignId('department_id')->nullable()->after('avatar_mime')
                ->constrained('departments', 'department_id')
                ->nullOnDelete();

            $table->string('specialization', 150)->nullable()->after('department_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['avatar', 'avatar_mime', 'department_id', 'specialization']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('username');
        });
    }
}
