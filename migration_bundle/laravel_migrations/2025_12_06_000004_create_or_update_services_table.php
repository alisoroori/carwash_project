<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the canonical services table.
 * 
 * Stores car wash service offerings.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('carwash_id')->constrained()->onDelete('cascade');
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->unsignedInteger('duration')->default(30)->comment('Duration in minutes');
                $table->string('category', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                // Indexes
                $table->index('carwash_id');
                $table->index('is_active');
                $table->index('category');
            });
        } else {
            Schema::table('services', function (Blueprint $table) {
                if (!Schema::hasColumn('services', 'category')) {
                    $table->string('category', 50)->nullable()->after('duration');
                }
                if (!Schema::hasColumn('services', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('category');
                }
                if (!Schema::hasColumn('services', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0)->after('is_active');
                }
                if (!Schema::hasColumn('services', 'updated_at')) {
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
        Schema::table('services', function (Blueprint $table) {
            $columns = ['category', 'is_active', 'sort_order'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
