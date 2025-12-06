<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the canonical payments table.
 * 
 * Stores payment records for bookings.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('transaction_id', 100)->nullable();
                $table->decimal('amount', 10, 2);
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->decimal('tax', 10, 2)->default(0.00);
                $table->decimal('discount', 10, 2)->default(0.00);
                $table->enum('payment_method', ['credit_card', 'cash', 'online_transfer', 'mobile_payment'])->default('cash');
                $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
                $table->datetime('payment_date')->nullable();
                $table->string('receipt_url', 255)->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable()->comment('Additional payment data');
                $table->timestamps();

                // Indexes
                $table->index('booking_id');
                $table->index('user_id');
                $table->index('status');
                $table->index('transaction_id');
            });
        } else {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->after('booking_id');
                }
                if (!Schema::hasColumn('payments', 'total_amount')) {
                    $table->decimal('total_amount', 10, 2)->nullable()->after('amount');
                }
                if (!Schema::hasColumn('payments', 'tax')) {
                    $table->decimal('tax', 10, 2)->default(0.00)->after('total_amount');
                }
                if (!Schema::hasColumn('payments', 'discount')) {
                    $table->decimal('discount', 10, 2)->default(0.00)->after('tax');
                }
                if (!Schema::hasColumn('payments', 'receipt_url')) {
                    $table->string('receipt_url', 255)->nullable()->after('payment_date');
                }
                if (!Schema::hasColumn('payments', 'metadata')) {
                    $table->json('metadata')->nullable()->after('notes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $columns = ['user_id', 'total_amount', 'tax', 'discount', 'receipt_url', 'metadata'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
