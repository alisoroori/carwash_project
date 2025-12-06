<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the promotions table.
 * 
 * Stores promotional campaigns and discount codes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('carwash_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('discount_value', 10, 2);
                $table->decimal('min_purchase', 10, 2)->nullable();
                $table->decimal('max_discount', 10, 2)->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('usage_count')->default(0);
                $table->integer('per_user_limit')->default(1);
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // Indexes
                $table->index('code');
                $table->index('carwash_id');
                $table->index('is_active');
                $table->index(['start_date', 'end_date']);
            });
        } else {
            Schema::table('promotions', function (Blueprint $table) {
                if (!Schema::hasColumn('promotions', 'carwash_id')) {
                    $table->foreignId('carwash_id')->nullable()->constrained()->onDelete('cascade')->after('id');
                }
                if (!Schema::hasColumn('promotions', 'per_user_limit')) {
                    $table->integer('per_user_limit')->default(1)->after('usage_count');
                }
                if (!Schema::hasColumn('promotions', 'updated_at')) {
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
        Schema::table('promotions', function (Blueprint $table) {
            $columns = ['carwash_id', 'per_user_limit'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('promotions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
