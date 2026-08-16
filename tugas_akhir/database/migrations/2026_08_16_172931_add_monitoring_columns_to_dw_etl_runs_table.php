<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dw_etl_runs', function (Blueprint $table) {
            $table->string('batch_code', 50)
                ->nullable()
                ->unique()
                ->after('run_uuid');

            $table->string('trigger_type', 20)
                ->nullable()
                ->after('batch_code');

            $table->foreignId('triggered_by_user_id')
                ->nullable()
                ->after('trigger_type')
                ->constrained('users')
                ->nullOnDelete();

            $table->unsignedBigInteger('source_rows')
                ->default(0)
                ->after('fact_rows');

            $table->unsignedBigInteger('target_rows')
                ->default(0)
                ->after('source_rows');

            $table->index(
                ['trigger_type', 'created_at'],
                'dw_etl_runs_trigger_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('dw_etl_runs', function (Blueprint $table) {
            $table->dropIndex('dw_etl_runs_trigger_created_idx');

            $table->dropForeign([
                'triggered_by_user_id',
            ]);

            $table->dropUnique([
                'batch_code',
            ]);

            $table->dropColumn([
                'batch_code',
                'trigger_type',
                'triggered_by_user_id',
                'source_rows',
                'target_rows',
            ]);
        });
    }
};