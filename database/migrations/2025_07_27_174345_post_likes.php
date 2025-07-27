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
        Schema::create('post_likes', function (Blueprint $table) {
            $table->id();
            
            // Matches posts.id (bigint)
            $table->unsignedBigInteger('post_id');
            
            // Matches users.id (string) - EXACTLY like your posts table
            $table->string('user_id');
            
            $table->timestamps();
            
            // Foreign keys (same as your posts table)
            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->onDelete('cascade');
                
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            // Prevent duplicate likes
            $table->unique(['post_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_likes');
    }
};
