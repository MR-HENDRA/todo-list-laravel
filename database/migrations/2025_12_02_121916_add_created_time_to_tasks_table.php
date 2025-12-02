<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'created_time')) {
                $table->time('created_time')->nullable()->after('date');
            }
            if (!Schema::hasColumn('tasks', 'created_date')) {
                $table->date('created_date')->nullable()->after('created_time');
            }
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'created_time')) {
                $table->dropColumn('created_time');
            }
            if (Schema::hasColumn('tasks', 'created_date')) {
                $table->dropColumn('created_date');
            }
        });
    }
};
