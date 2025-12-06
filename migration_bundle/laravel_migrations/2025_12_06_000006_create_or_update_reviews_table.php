<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the canonical reviews table.
 * 
 * Stores customer reviews for car washes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('carwash_id')->constrained()->onDelete('cascade');
                $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
                $table->unsignedTinyInteger('rating')->comment('1-5 stars');
                $table->text('comment')->nullable();
                $table->text('owner_reply')->nullable();
                $table->timestamp('owner_replied_at')->nullable();
                $table->boolean('is_verified')->default(false)->comment('Verified purchase');
                $table->boolean('is_visible')->default(true);
                $table->timestamps();

                // Indexes
                $table->index('carwash_id');
                $table->index('user_id');
                $table->index('rating');
                $table->index('is_visible');
                $table->unique(['user_id', 'booking_id'], 'unique_review_per_booking');
            });
        } else {
            Schema::table('reviews', function (Blueprint $table) {
                if (!Schema::hasColumn('reviews', 'booking_id')) {
                    $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null')->after('carwash_id');
                }
                if (!Schema::hasColumn('reviews', 'owner_reply')) {
                    $table->text('owner_reply')->nullable()->after('comment');
                }
                if (!Schema::hasColumn('reviews', 'owner_replied_at')) {
                    $table->timestamp('owner_replied_at')->nullable()->after('owner_reply');
                }
                if (!Schema::hasColumn('reviews', 'is_verified')) {
                    $table->boolean('is_verified')->default(false)->after('owner_replied_at');
                }
                if (!Schema::hasColumn('reviews', 'is_visible')) {
                    $table->boolean('is_visible')->default(true)->after('is_verified');
                }
                if (!Schema::hasColumn('reviews', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Drop foreign key first if it exists
            try {
                $table->dropForeign(['booking_id']);
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }
            
            $columns = ['booking_id', 'owner_reply', 'owner_replied_at', 'is_verified', 'is_visible'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
