<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the canonical user_vehicles table.
 * 
 * Stores vehicles registered by customers.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('user_vehicles')) {
            Schema::create('user_vehicles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('brand', 50);
                $table->string('model', 50);
                $table->string('year', 4)->nullable();
                $table->string('color', 30)->nullable();
                $table->string('license_plate', 20)->nullable();
                $table->enum('vehicle_type', ['sedan', 'suv', 'hatchback', 'truck', 'van', 'motorcycle', 'other'])->default('sedan');
                $table->string('image', 255)->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                // Indexes
                $table->index('user_id');
                $table->index('license_plate');
            });
        } else {
            Schema::table('user_vehicles', function (Blueprint $table) {
                if (!Schema::hasColumn('user_vehicles', 'year')) {
                    $table->string('year', 4)->nullable()->after('model');
                }
                if (!Schema::hasColumn('user_vehicles', 'vehicle_type')) {
                    $table->enum('vehicle_type', ['sedan', 'suv', 'hatchback', 'truck', 'van', 'motorcycle', 'other'])->default('sedan')->after('license_plate');
                }
                if (!Schema::hasColumn('user_vehicles', 'image')) {
                    $table->string('image', 255)->nullable()->after('vehicle_type');
                }
                if (!Schema::hasColumn('user_vehicles', 'is_default')) {
                    $table->boolean('is_default')->default(false)->after('image');
                }
                if (!Schema::hasColumn('user_vehicles', 'updated_at')) {
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
        Schema::table('user_vehicles', function (Blueprint $table) {
            $columns = ['year', 'vehicle_type', 'image', 'is_default'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('user_vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
