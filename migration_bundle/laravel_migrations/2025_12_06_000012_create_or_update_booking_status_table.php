<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Create the booking_status lookup table.
 * 
 * Stores available booking status options with labels.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('booking_status')) {
            Schema::create('booking_status', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 100);
                $table->string('name_tr', 100)->nullable()->comment('Turkish translation');
                $table->string('color', 20)->nullable()->comment('CSS color for UI');
                $table->string('icon', 50)->nullable()->comment('Icon class');
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_final')->default(false)->comment('Whether this is a terminal state');
                $table->timestamps();
            });

            // Seed default statuses
            DB::table('booking_status')->insert([
                [
                    'code' => 'pending',
                    'name' => 'Pending',
                    'name_tr' => 'Bekliyor',
                    'color' => '#FFA500',
                    'icon' => 'fa-clock',
                    'sort_order' => 1,
                    'is_active' => true,
                    'is_final' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'confirmed',
                    'name' => 'Confirmed',
                    'name_tr' => 'Onaylandı',
                    'color' => '#28A745',
                    'icon' => 'fa-check',
                    'sort_order' => 2,
                    'is_active' => true,
                    'is_final' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'in_progress',
                    'name' => 'In Progress',
                    'name_tr' => 'İşlemde',
                    'color' => '#007BFF',
                    'icon' => 'fa-spinner',
                    'sort_order' => 3,
                    'is_active' => true,
                    'is_final' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'completed',
                    'name' => 'Completed',
                    'name_tr' => 'Tamamlandı',
                    'color' => '#17A2B8',
                    'icon' => 'fa-check-circle',
                    'sort_order' => 4,
                    'is_active' => true,
                    'is_final' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'cancelled',
                    'name' => 'Cancelled',
                    'name_tr' => 'İptal',
                    'color' => '#DC3545',
                    'icon' => 'fa-times-circle',
                    'sort_order' => 5,
                    'is_active' => true,
                    'is_final' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'no_show',
                    'name' => 'No Show',
                    'name_tr' => 'Gelmedi',
                    'color' => '#6C757D',
                    'icon' => 'fa-user-slash',
                    'sort_order' => 6,
                    'is_active' => true,
                    'is_final' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        } else {
            Schema::table('booking_status', function (Blueprint $table) {
                if (!Schema::hasColumn('booking_status', 'name_tr')) {
                    $table->string('name_tr', 100)->nullable()->after('name');
                }
                if (!Schema::hasColumn('booking_status', 'color')) {
                    $table->string('color', 20)->nullable()->after('name_tr');
                }
                if (!Schema::hasColumn('booking_status', 'icon')) {
                    $table->string('icon', 50)->nullable()->after('color');
                }
                if (!Schema::hasColumn('booking_status', 'is_final')) {
                    $table->boolean('is_final')->default(false)->after('is_active');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_status', function (Blueprint $table) {
            $columns = ['name_tr', 'color', 'icon', 'is_final'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('booking_status', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
