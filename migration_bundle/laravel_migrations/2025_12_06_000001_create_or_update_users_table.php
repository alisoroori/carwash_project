<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the canonical users table.
 * 
 * This migration creates the users table if it doesn't exist and adds
 * any missing columns if it does exist.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('email', 150)->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password', 255);
                $table->string('phone', 20)->nullable();
                $table->string('address', 255)->nullable();
                $table->enum('role', ['admin', 'customer', 'owner'])->default('customer');
                $table->boolean('is_active')->default(true);
                $table->string('profile_image', 255)->nullable();
                $table->string('remember_token', 100)->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable()->after('email');
                }
                if (!Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('role');
                }
                if (!Schema::hasColumn('users', 'remember_token')) {
                    $table->string('remember_token', 100)->nullable()->after('profile_image');
                }
                if (!Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('remember_token');
                }
                if (!Schema::hasColumn('users', 'updated_at')) {
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
        // Only drop columns added by this migration, not the whole table
        Schema::table('users', function (Blueprint $table) {
            $columns = ['email_verified_at', 'is_active', 'remember_token', 'last_login_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
