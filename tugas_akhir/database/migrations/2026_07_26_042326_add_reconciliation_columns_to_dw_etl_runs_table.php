<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dw_etl_runs')) {
            return;
        }

        Schema::table('dw_etl_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('dw_etl_runs', 'reconciliation_status')) {
                $table->string('reconciliation_status', 20)
                    ->default('pending')
                    ->after('duration_ms');
            }

            if (! Schema::hasColumn('dw_etl_runs', 'reconciled_at')) {
                $table->timestamp('reconciled_at')
                    ->nullable()
                    ->after('reconciliation_status');
            }

            if (! Schema::hasColumn('dw_etl_runs', 'reconciliation_message')) {
                $table->text('reconciliation_message')
                    ->nullable()
                    ->after('reconciled_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dw_etl_runs')) {
            return;
        }

        $columns = collect([
            'reconciliation_status',
            'reconciled_at',
            'reconciliation_message',
        ])
            ->filter(
                fn (string $column): bool => Schema::hasColumn(
                    'dw_etl_runs',
                    $column
                )
            )
            ->values()
            ->all();

        if ($columns === []) {
            return;
        }

        Schema::table('dw_etl_runs', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};