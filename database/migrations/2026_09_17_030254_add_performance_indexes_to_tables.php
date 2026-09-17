<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memos', function (Blueprint $table) {
            $table->index('status');
            $table->index(['area_manager_id', 'status']);
            $table->index(['created_by', 'status']);
            $table->index('updated_at');
            $table->index('submitted_at');
        });

        Schema::table('memo_approvals', function (Blueprint $table) {
            $table->index('action');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'area_id']);
            $table->index(['role', 'branch_id']);
        });

        Schema::table('memo_templates', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('memos', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['area_manager_id', 'status']);
            $table->dropIndex(['created_by', 'status']);
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['submitted_at']);
        });

        Schema::table('memo_approvals', function (Blueprint $table) {
            $table->dropIndex(['action']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_read']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'area_id']);
            $table->dropIndex(['role', 'branch_id']);
        });

        Schema::table('memo_templates', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
