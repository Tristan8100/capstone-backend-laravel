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
        Schema::create('careers', function (Blueprint $table) {
            $table->id(); // auto-increment PK
            $table->string('user_id'); // FK to users.id (string)
            $table->string('title');
            $table->string('company');
            $table->text('description')->nullable();
            $table->json('skills_used')->nullable();
            $table->string('fit_category')->nullable(); // "Related" or "Not Related"
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
