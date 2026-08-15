<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dw_etl_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('run_uuid')->unique();
            $table->string('status', 20);
            $table->unsignedBigInteger('dimension_rows')->default(0);
            $table->unsignedBigInteger('fact_rows')->default(0);
            $table->json('table_row_counts')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(
                ['status', 'started_at'],
                'dw_etl_runs_status_started_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dw_etl_runs');
    }
};