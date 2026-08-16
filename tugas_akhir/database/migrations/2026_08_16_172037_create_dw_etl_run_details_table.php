<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dw_etl_run_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dw_etl_run_id')
                ->constrained('dw_etl_runs')
                ->cascadeOnDelete();

            $table->string('step_key', 100);
            $table->string('step_name', 150);
            $table->string('step_type', 30);
            $table->unsignedInteger('step_order');

            $table->string('status', 30)->default('processing');

            $table->unsignedBigInteger('source_rows')->default(0);
            $table->unsignedBigInteger('target_rows')->default(0);

            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();

            $table->text('error_message')->nullable();

            $table->boolean('rolled_back')->default(false);

            $table->timestamps();

            $table->unique(
                ['dw_etl_run_id', 'step_key'],
                'dw_etl_run_detail_step_unique'
            );

            $table->index(
                ['dw_etl_run_id', 'step_order'],
                'dw_etl_run_detail_order_idx'
            );

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dw_etl_run_details');
    }
};