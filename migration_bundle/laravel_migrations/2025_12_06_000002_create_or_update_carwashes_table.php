<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the canonical carwashes table.
 * 
 * This is the main table storing all car wash business information.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('carwashes')) {
            Schema::create('carwashes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('address', 255)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('district', 100)->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('opening_time', 10)->default('09:00');
                $table->string('closing_time', 10)->default('18:00');
                $table->json('working_days')->nullable();
                $table->decimal('average_rating', 3, 2)->default(0.00);
                $table->unsignedInteger('total_reviews')->default(0);
                $table->enum('status', ['pending', 'active', 'inactive', 'suspended'])->default('pending');
                $table->string('logo', 255)->nullable();
                $table->string('cover_image', 255)->nullable();
                $table->json('gallery_images')->nullable();
                $table->json('features')->nullable();
                $table->boolean('has_waiting_area')->default(false);
                $table->boolean('has_wifi')->default(false);
                $table->boolean('accepts_credit_card')->default(false);
                $table->timestamps();

                // Indexes
                $table->index('city');
                $table->index('district');
                $table->index('status');
                $table->index('average_rating');
            });
        } else {
            Schema::table('carwashes', function (Blueprint $table) {
                if (!Schema::hasColumn('carwashes', 'average_rating')) {
                    $table->decimal('average_rating', 3, 2)->default(0.00)->after('total_reviews');
                }
                if (!Schema::hasColumn('carwashes', 'total_reviews')) {
                    $table->unsignedInteger('total_reviews')->default(0)->after('average_rating');
                }
                if (!Schema::hasColumn('carwashes', 'district')) {
                    $table->string('district', 100)->nullable()->after('city');
                }
                if (!Schema::hasColumn('carwashes', 'working_days')) {
                    $table->json('working_days')->nullable()->after('closing_time');
                }
                if (!Schema::hasColumn('carwashes', 'features')) {
                    $table->json('features')->nullable()->after('gallery_images');
                }
                if (!Schema::hasColumn('carwashes', 'updated_at')) {
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
        Schema::table('carwashes', function (Blueprint $table) {
            $columns = ['average_rating', 'total_reviews', 'district', 'working_days', 'features'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('carwashes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
