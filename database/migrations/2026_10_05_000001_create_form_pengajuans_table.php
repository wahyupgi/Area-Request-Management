<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_pengajuans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('template');
            $table->string('title');
            $table->json('data');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('area_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['area_manager_id', 'status']);
            $table->index(['created_by', 'updated_at']);
        });

        DB::table('berita_acaras')
            ->where('meta->document_kind', 'form_pengajuan')
            ->orderBy('id')
            ->chunkById(100, function ($documents): void {
                foreach ($documents as $document) {
                    $meta = json_decode($document->meta, true) ?: [];
                    DB::table('form_pengajuans')->insert([
                        'id' => $document->id,
                        'code' => $document->code,
                        'template' => $meta['template'],
                        'title' => $document->title,
                        'data' => json_encode($meta['request_data'] ?? []),
                        'attachment_path' => $document->attachment_path,
                        'attachment_name' => $document->attachment_name,
                        'branch_id' => $document->branch_id,
                        'created_by' => $document->created_by,
                        'area_manager_id' => $document->area_manager_id,
                        'status' => $document->status,
                        'submitted_at' => $document->submitted_at,
                        'created_at' => $document->created_at,
                        'updated_at' => $document->updated_at,
                    ]);
                }
            });

        DB::table('berita_acaras')
            ->where('meta->document_kind', 'form_pengajuan')
            ->delete();
    }

    public function down(): void
    {
        if (Schema::hasTable('form_pengajuans') && Schema::hasTable('berita_acaras')) {
            DB::table('form_pengajuans')->orderBy('id')->chunkById(100, function ($forms): void {
                foreach ($forms as $form) {
                    DB::table('berita_acaras')->insert([
                        'id' => $form->id,
                        'code' => $form->code,
                        'title' => $form->title,
                        'meta' => json_encode([
                            'document_kind' => 'form_pengajuan',
                            'template' => $form->template,
                            'request_data' => json_decode($form->data, true) ?: [],
                        ]),
                        'attachment_path' => $form->attachment_path,
                        'attachment_name' => $form->attachment_name,
                        'branch_id' => $form->branch_id,
                        'created_by' => $form->created_by,
                        'area_manager_id' => $form->area_manager_id,
                        'status' => $form->status,
                        'submitted_at' => $form->submitted_at,
                        'created_at' => $form->created_at,
                        'updated_at' => $form->updated_at,
                    ]);
                }
            });
        }

        Schema::dropIfExists('form_pengajuans');
    }
};
