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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->boolean('email_verified');
            $table->string('password')->nullable();
            $table->string('picture') ->nullable();
            $table->integer('planType') ->default(0); /* one is for free plan 1is for basic 2 is for pro and 3 is for anually */
            $table -> date("expiry_date") -> nullable(); /* date of which the plan is expiring */
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
