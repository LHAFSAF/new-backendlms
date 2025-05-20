<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTypeEnumInResourcesTable extends Migration
{
    public function up()
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->enum('type', ['video', 'pdf', 'link', 'text', 'image', 'youtube'])->change();
        });
    }

    public function down()
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->enum('type', ['video', 'pdf', 'link', 'text', 'image'])->change();
        });
    }
}
