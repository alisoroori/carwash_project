<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migrate data from legacy tables to canonical tables.
 * 
 * This migration handles:
 * - carwash_profiles → carwashes
 * - vehicles → user_vehicles
 * - Generate booking numbers
 * - Calculate carwash rating statistics
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Migrate carwash_profiles to carwashes (if table exists)
        if (Schema::hasTable('carwash_profiles') && Schema::hasTable('carwashes')) {
            $profiles = DB::table('carwash_profiles')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('carwashes')
                        ->whereColumn('carwashes.user_id', 'carwash_profiles.user_id');
                })
                ->get();
            
            foreach ($profiles as $profile) {
                DB::table('carwashes')->insert([
                    'user_id' => $profile->user_id,
                    'name' => $profile->business_name ?? $profile->name ?? 'Unknown',
                    'description' => $profile->description ?? null,
                    'address' => $profile->address ?? null,
                    'city' => $profile->city ?? null,
                    'phone' => $profile->phone ?? null,
                    'opening_time' => $profile->opening_time ?? '09:00',
                    'closing_time' => $profile->closing_time ?? '18:00',
                    'status' => $profile->status ?? 'pending',
                    'created_at' => $profile->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Migrate vehicles to user_vehicles (if table exists)
        if (Schema::hasTable('vehicles') && Schema::hasTable('user_vehicles')) {
            $vehicles = DB::table('vehicles')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('user_vehicles')
                        ->whereColumn('user_vehicles.user_id', 'vehicles.user_id')
                        ->whereColumn('user_vehicles.license_plate', 'vehicles.plate_number');
                })
                ->get();

            foreach ($vehicles as $vehicle) {
                DB::table('user_vehicles')->insert([
                    'user_id' => $vehicle->user_id,
                    'brand' => $vehicle->brand ?? $vehicle->make ?? 'Unknown',
                    'model' => $vehicle->model ?? 'Unknown',
                    'year' => $vehicle->year ?? null,
                    'color' => $vehicle->color ?? null,
                    'license_plate' => $vehicle->plate_number ?? $vehicle->license_plate ?? null,
                    'vehicle_type' => $vehicle->type ?? 'sedan',
                    'created_at' => $vehicle->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Generate booking numbers for bookings without one
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'booking_number')) {
            $year = date('Y');
            $bookings = DB::table('bookings')
                ->whereNull('booking_number')
                ->orWhere('booking_number', '')
                ->get();

            foreach ($bookings as $booking) {
                $bookingNumber = 'BK' . $year . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
                DB::table('bookings')
                    ->where('id', $booking->id)
                    ->update(['booking_number' => $bookingNumber]);
            }
        }

        // 4. Calculate carwash rating statistics
        if (Schema::hasTable('carwashes') && Schema::hasTable('reviews')) {
            $stats = DB::table('reviews')
                ->select('carwash_id')
                ->selectRaw('ROUND(AVG(rating), 2) as avg_rating')
                ->selectRaw('COUNT(*) as review_count')
                ->groupBy('carwash_id')
                ->get();

            foreach ($stats as $stat) {
                DB::table('carwashes')
                    ->where('id', $stat->carwash_id)
                    ->update([
                        'average_rating' => $stat->avg_rating,
                        'total_reviews' => $stat->review_count,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migrations are not easily reversible
        // Recommend restoring from backup if needed
        
        // Reset statistics
        if (Schema::hasTable('carwashes')) {
            DB::table('carwashes')->update([
                'average_rating' => 0.00,
                'total_reviews' => 0,
            ]);
        }

        // Note: We don't delete migrated records as they may have been modified
        // Restore from backup if full rollback is needed
    }
};
