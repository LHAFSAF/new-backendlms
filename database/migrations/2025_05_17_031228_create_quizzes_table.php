<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('quizzes', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('module_id')->nullable(); // ou course_id si c'est pour tout le cours
        $table->string('title');
        $table->integer('threshold')->default(50); // seuil de réussite en %
        $table->timestamps();

        $table->foreign('module_id')->references('id')->on('modules')->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
