<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita_acara_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berita_acara_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->timestamps();
        });

        Schema::create('form_pengajuan_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_pengajuan_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_pengajuan_attachments');
        Schema::dropIfExists('berita_acara_attachments');
    }
};
