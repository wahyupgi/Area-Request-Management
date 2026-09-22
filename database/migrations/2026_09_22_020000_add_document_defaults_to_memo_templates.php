<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memo_templates', function (Blueprint $table) {
            $table->json('document_defaults')->nullable()->after('field_schema');
        });

        DB::table('memo_templates')->update([
            'document_defaults' => json_encode([
                'direktorat' => 'Regional Branch Office',
                'divisi' => 'Branch Leader',
                'perihal' => '',
                'kepada' => '',
                'kepada_jabatan' => '',
                'penyetuju_akhir' => '',
                'lampiran' => '',
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::table('memo_templates', function (Blueprint $table) {
            $table->dropColumn('document_defaults');
        });
    }
};