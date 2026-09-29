<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memos', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('memos')->whereNull('template_id')->exists()) {
            throw new RuntimeException('Cannot require memo templates while blank memos exist.');
        }

        Schema::table('memos', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable(false)->change();
        });
    }
};