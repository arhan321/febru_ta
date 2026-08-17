<?php

namespace App\Filament\Admin\DataWarehouseWidgets;

use App\Models\DwEtlRun;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\View\View;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class DataWarehouseEtlHistoryWidget extends TableWidget
{
    protected static ?string $heading = 'Riwayat Proses ETL';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => DwEtlRun::query()
                    ->with('triggeredByUser')
            )
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('batch_code')
                    ->label('Batch ETL')
                    ->placeholder('-')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('trigger_type')
                    ->label('Pemicu')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'manual' => 'Manual',
                            'scheduler' => 'Scheduler',
                            default => '-',
                        }
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'manual' => 'info',
                            'scheduler' => 'gray',
                            default => 'gray',
                        }
                    ),

                TextColumn::make('triggered_by')
                    ->label('Dijalankan Oleh')
                    ->state(function (DwEtlRun $record): string {
                        if ($record->trigger_type === 'scheduler') {
                            return 'Sistem';
                        }

                        return $record->triggeredByUser?->name ?? 'CLI / Sistem';
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'running' => 'Processing',
                            'success' => 'Success',
                            'failed' => 'Failed',
                            default => ucfirst((string) $state),
                        }
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'running' => 'warning',
                            'success' => 'success',
                            'failed' => 'danger',
                            default => 'gray',
                        }
                    ),

                TextColumn::make('source_rows')
                    ->label('Baris Sumber')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('target_rows')
                    ->label('Baris Target')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('duration_ms')
                    ->label('Durasi')
                    ->formatStateUsing(function ($state): string {
                        if ($state === null) {
                            return '-';
                        }

                        return number_format(
                            ((int) $state) / 1000,
                            2,
                            ',',
                            '.'
                        ) . ' detik';
                    }),

                TextColumn::make('created_at')
                    ->label('Waktu Mulai')
                    ->dateTime('d M Y H:i:s'),

                TextColumn::make('finished_at')
                    ->label('Waktu Selesai')
                    ->dateTime('d M Y H:i:s')
                    ->placeholder('-'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->recordActions([
            Action::make('detail')
                ->label('Detail')
                ->modalHeading(
                    fn (DwEtlRun $record): string =>
                        'Detail Proses ETL - ' . ($record->batch_code ?? 'Tanpa Kode Batch')
                )
                ->modalContent(
                    fn (DwEtlRun $record): View => view(
                        'filament.admin.data-warehouse-widgets.etl-history-detail',
                        [
                            'run' => $record->load([
                                'details',
                                'triggeredByUser',
                            ]),
                        ]
                    )
                )
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalWidth(Width::SevenExtraLarge),
        ]);
    }
}