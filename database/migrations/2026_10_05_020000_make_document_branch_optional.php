<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['memos', 'berita_acaras', 'form_pengajuans'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['branch_id']);
                $table->unsignedBigInteger('branch_id')->nullable()->change();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (DB::table($tableName)->whereNull('branch_id')->exists()) {
                throw new RuntimeException("Cannot require a branch for existing {$tableName} records.");
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['branch_id']);
                $table->unsignedBigInteger('branch_id')->nullable(false)->change();
                $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            });
        }
    }
};
