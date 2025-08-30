<?php
// database/migrations/add_performance_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['created_by', 'transaction_date'], 'idx_transactions_user_date');
            $table->index(['category_id', 'status', 'transaction_date'], 'idx_transactions_category_status_date');
            $table->index(['type', 'status', 'amount'], 'idx_transactions_type_status_amount');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'idx_audit_logs_polymorphic');
            $table->index(['user_id', 'event', 'created_at'], 'idx_audit_logs_user_event');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index(['status', 'type', 'created_at'], 'idx_reports_status_type_date');
            $table->index(['created_by', 'status'], 'idx_reports_user_status');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_user_date');
            $table->dropIndex('idx_transactions_category_status_date');
            $table->dropIndex('idx_transactions_type_status_amount');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_polymorphic');
            $table->dropIndex('idx_audit_logs_user_event');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('idx_reports_status_type_date');
            $table->dropIndex('idx_reports_user_status');
        });
    }
};