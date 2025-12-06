<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the canonical ui_labels table.
 * 
 * Stores internationalization labels for the UI.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ui_labels')) {
            Schema::create('ui_labels', function (Blueprint $table) {
                $table->id();
                $table->string('label_key', 100);
                $table->string('language_code', 5)->default('tr');
                $table->text('label_value');
                $table->string('context', 50)->nullable()->comment('e.g., common, booking, review');
                $table->timestamps();

                // Unique constraint
                $table->unique(['label_key', 'language_code'], 'unique_label_per_language');
                
                // Indexes
                $table->index('language_code');
                $table->index('context');
            });
        } else {
            Schema::table('ui_labels', function (Blueprint $table) {
                if (!Schema::hasColumn('ui_labels', 'context')) {
                    $table->string('context', 50)->nullable()->after('label_value');
                }
                if (!Schema::hasColumn('ui_labels', 'updated_at')) {
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
        Schema::table('ui_labels', function (Blueprint $table) {
            $columns = ['context'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('ui_labels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
