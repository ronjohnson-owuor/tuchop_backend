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
        Schema::create('mavfits', function (Blueprint $table) {
            $table->id();
            $table -> text("firstname");
            $table -> text("lastname");
            $table -> text("email");
            $table -> integer("phone");
            $table -> text("info");
            $table -> text("traffic");
            $table -> text("question");
            $table -> text("promo") -> nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mavfits');
    }
};
