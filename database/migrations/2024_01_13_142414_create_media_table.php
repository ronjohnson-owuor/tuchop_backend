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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table ->text('media_name');
            $table ->text('user_id');
            $table ->text('topic_id');
            $table ->text('subtopic_id');
            $table ->text('media_url');
            $table -> text("sourceId") -> nullable();
            $table ->text('media_type'); /* 0 for image and 1 for files */
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medias');
    }
};
