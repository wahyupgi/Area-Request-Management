<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->index(['area_manager_id', 'updated_at'], 'ba_am_updated_idx');
            $table->index(['created_by', 'updated_at'], 'ba_creator_updated_idx');
            $table->index('status', 'ba_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->dropIndex('ba_am_updated_idx');
            $table->dropIndex('ba_creator_updated_idx');
            $table->dropIndex('ba_status_idx');
        });
    }
};
