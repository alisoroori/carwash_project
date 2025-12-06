<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the canonical favorites table.
 * 
 * Stores user's favorite car washes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('favorites')) {
            Schema::create('favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('carwash_id')->constrained()->onDelete('cascade');
                $table->timestamps();

                // Unique constraint - user can only favorite a carwash once
                $table->unique(['user_id', 'carwash_id'], 'unique_user_carwash_favorite');
                
                // Indexes
                $table->index('user_id');
                $table->index('carwash_id');
            });
        } else {
            Schema::table('favorites', function (Blueprint $table) {
                if (!Schema::hasColumn('favorites', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table, just remove added columns if any
        Schema::table('favorites', function (Blueprint $table) {
            // No columns to drop - table was created fresh
        });
    }
};
