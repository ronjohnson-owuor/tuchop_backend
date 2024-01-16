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
        Schema::create('promptdatas', function (Blueprint $table) {
            $table->id();
            $table ->text('module_id');
            $table ->text('submodule_id');
            $table ->text('module_owner_id');
            $table ->text('question');
            $table ->text('answer');
            $table ->text('follow_up_question');
            $table ->text('videos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promptdatas');
    }
};
