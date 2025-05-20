<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
{
    Schema::table('course_progress', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->integer('progress')->default(0)->after('course_id');
    });
}


    /**
     * Reverse the migrations.
     */
public function down()
{
    Schema::table('course_progress', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->dropColumn('progress');
    });
}

};
