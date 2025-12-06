<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the canonical bookings table.
 * 
 * Stores all booking/appointment records.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->string('booking_number', 20)->unique();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('carwash_id')->constrained()->onDelete('cascade');
                $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('vehicle_id')->nullable()->constrained('user_vehicles')->onDelete('set null');
                $table->date('booking_date');
                $table->time('booking_time');
                $table->string('customer_name', 100)->nullable();
                $table->string('customer_phone', 20)->nullable();
                $table->string('customer_email', 150)->nullable();
                $table->text('notes')->nullable();
                $table->decimal('total_price', 10, 2)->nullable();
                $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
                $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
                $table->string('payment_method', 50)->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('booking_date');
                $table->index('status');
                $table->index(['user_id', 'status']);
                $table->index(['carwash_id', 'booking_date']);
            });
        } else {
            Schema::table('bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('bookings', 'booking_number')) {
                    $table->string('booking_number', 20)->nullable()->unique()->after('id');
                }
                if (!Schema::hasColumn('bookings', 'customer_name')) {
                    $table->string('customer_name', 100)->nullable()->after('booking_time');
                }
                if (!Schema::hasColumn('bookings', 'customer_phone')) {
                    $table->string('customer_phone', 20)->nullable()->after('customer_name');
                }
                if (!Schema::hasColumn('bookings', 'customer_email')) {
                    $table->string('customer_email', 150)->nullable()->after('customer_phone');
                }
                if (!Schema::hasColumn('bookings', 'payment_status')) {
                    $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending')->after('status');
                }
                if (!Schema::hasColumn('bookings', 'payment_method')) {
                    $table->string('payment_method', 50)->nullable()->after('payment_status');
                }
                if (!Schema::hasColumn('bookings', 'confirmed_at')) {
                    $table->timestamp('confirmed_at')->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('bookings', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('confirmed_at');
                }
                if (!Schema::hasColumn('bookings', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('completed_at');
                }
                if (!Schema::hasColumn('bookings', 'cancellation_reason')) {
                    $table->text('cancellation_reason')->nullable()->after('cancelled_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = [
                'booking_number', 'customer_name', 'customer_phone', 'customer_email',
                'payment_status', 'payment_method', 'confirmed_at', 'completed_at',
                'cancelled_at', 'cancellation_reason'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
