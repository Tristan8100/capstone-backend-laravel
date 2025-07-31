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
        Schema::create('token_validations', function (Blueprint $table) {
            $table->id();
            $table->string('user_id'); // stores both string UUIDs and integer IDs as strings, NO FK BECAUSE BOTH USER AND ADMIN
            $table->string('token_bearer')->unique();
            $table->string('user_agent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_validations');
    }
};
