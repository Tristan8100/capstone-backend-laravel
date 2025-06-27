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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->onDelete('cascade');
            
            $table->string('user_id'); // Must be string to match users.id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
            
            $table->text('content');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
