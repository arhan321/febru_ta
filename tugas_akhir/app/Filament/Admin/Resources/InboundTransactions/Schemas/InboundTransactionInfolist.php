<?php

namespace App\Filament\Admin\Resources\InboundTransactions\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class InboundTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Barang Masuk')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('transaction_number')
                                    ->label('No. Transaksi')
                                    ->badge(),

                                TextEntry::make('transaction_date')
                                    ->label('Tanggal')
                                    ->date('d M Y'),

                                TextEntry::make('invoice_number')
                                    ->label('No. Invoice')
                                    ->placeholder('-'),

                                TextEntry::make('supplier.name')
                                    ->label('Supplier')
                                    ->placeholder('-'),

                                TextEntry::make('warehouse.name')
                                    ->label('Gudang Tujuan')
                                    ->badge(),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'Pending',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        'cancelled' => 'Dibatalkan',
                                        default => ucfirst($state),
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'cancelled' => 'gray',
                                        default => 'gray',
                                    }),

                                TextEntry::make('source')
                                    ->label('Sumber')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'import_excel' => 'Import Excel',
                                        'import_database' => 'Import Database',
                                        null, '' => 'Manual',
                                        default => ucfirst(str_replace('_', ' ', $state)),
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'import_excel' => 'info',
                                        'import_database' => 'warning',
                                        default => 'gray',
                                    }),
                            ]),

                        TextEntry::make('note')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Detail Item Barang Masuk')
                    ->description('Menampilkan data item, termasuk data asli dari Excel seperti M3, Harga/M3, dan Jumlah Excel.')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('Daftar Item')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('product_code_snapshot')
                                            ->label('Kode Barang')
                                            ->placeholder('-'),

                                        TextEntry::make('product_name_snapshot')
                                            ->label('Nama Barang')
                                            ->placeholder('-')
                                            ->columnSpan(2),

                                        TextEntry::make('unit_name_snapshot')
                                            ->label('Satuan')
                                            ->placeholder('-'),

                                        TextEntry::make('qty')
                                            ->label('QTY')
                                            ->formatStateUsing(fn ($state): string => $state === null
                                                ? '-'
                                                : rtrim(rtrim(number_format((float) $state, 6, ',', '.'), '0'), ','))
                                            ->badge(),

                                        TextEntry::make('volume_m3')
                                            ->label('M3 Excel')
                                            ->formatStateUsing(fn ($state): string => $state === null
                                                ? '-'
                                                : rtrim(rtrim(number_format((float) $state, 6, ',', '.'), '0'), ',') . ' M³')
                                            ->placeholder('-'),

                                        TextEntry::make('price_per_m3')
                                            ->label('Harga/M3 Excel')
                                            ->money('IDR')
                                            ->placeholder('-'),

                                        TextEntry::make('excel_subtotal')
                                            ->label('Jumlah Excel')
                                            ->money('IDR')
                                            ->placeholder('-'),

                                        TextEntry::make('unit_cost')
                                            ->label('Unit Cost Sistem')
                                            ->money('IDR'),

                                        TextEntry::make('subtotal')
                                            ->label('Subtotal Sistem')
                                            ->money('IDR')
                                            ->weight('bold'),

                                        TextEntry::make('note')
                                            ->label('Catatan Item')
                                            ->placeholder('-')
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Total Dokumen')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('sub_total')
                                    ->label('Sub Total')
                                    ->money('IDR'),

                                TextEntry::make('discount_amount')
                                    ->label('Diskon')
                                    ->money('IDR'),

                                TextEntry::make('other_cost')
                                    ->label('Biaya Lain')
                                    ->money('IDR'),

                                TextEntry::make('grand_total')
                                    ->label('Grand Total')
                                    ->money('IDR')
                                    ->weight('bold'),
                            ]),
                    ]),

                Section::make('Informasi Approval')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('submittedBy.name')
                                    ->label('Submitted By')
                                    ->placeholder('-'),

                                TextEntry::make('approvedBy.name')
                                    ->label('Approved By')
                                    ->placeholder('-'),

                                TextEntry::make('approved_at')
                                    ->label('Approved At')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),

                                TextEntry::make('rejectedBy.name')
                                    ->label('Rejected By')
                                    ->placeholder('-'),

                                TextEntry::make('rejected_at')
                                    ->label('Rejected At')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),

                                TextEntry::make('rejection_reason')
                                    ->label('Alasan Reject')
                                    ->placeholder('-'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}