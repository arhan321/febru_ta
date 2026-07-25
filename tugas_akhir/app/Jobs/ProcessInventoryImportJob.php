<?php

namespace App\Jobs;

use Throwable;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use App\Models\InboundTransaction;
use App\Models\InventoryImportLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\OutboundTransaction;
use App\Models\InboundTransactionItem;
use Illuminate\Database\Query\Builder;
use Illuminate\Queue\SerializesModels;
use App\Models\OutboundTransactionItem;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\Schema as DBSchema;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;


class ProcessInventoryImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(
        public int $logId,
        public string $excelFile,
        public array $state
    ) {
    }

    public function handle(): void
    {
        $log = InventoryImportLog::query()->find($this->logId);

        if (! $log) {
            return;
        }

        try {
            $sourceType = $this->sourceType();
            $transactionType = $this->transactionType();

            $log->update([
                'status' => 'processing',
                'message' => $sourceType === 'database'
                    ? 'Database eksternal sedang diproses oleh queue worker.'
                    : 'File Excel sedang diproses oleh queue worker.',
            ]);

            if ($sourceType === 'database') {
                $result = $this->importFromExternalDatabase($transactionType, $log);
            } else {
                $filePath = Storage::disk('local')->path($this->excelFile);

                if (! file_exists($filePath)) {
                    throw new \Exception('File Excel tidak ditemukan di storage: ' . $this->excelFile);
                }

                $result = $transactionType === 'inbound'
                    ? $this->importInboundFlexibleExcel($filePath, $log)
                    : $this->importOutboundHistoricalExcel($filePath, $this->state, $log);
            }

            $log->update([
                'total_rows' => $result['total_rows'],
                'imported_rows' => $result['imported_rows'],
                'skipped_rows' => $result['skipped_rows'],
                'status' => 'success',
                'message' => $result['message'],
                'error_message' => $result['error_message'],
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);

            $log->update([
                'status' => 'failed',
                'message' => 'Import gagal.',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);
        }
    }

    private function sourceType(): string
    {
        $value = strtolower((string) ($this->state['source_type']
            ?? $this->state['source']
            ?? $this->state['data_source']
            ?? $this->state['import_source']
            ?? 'excel'));

        if (
            str_contains($value, 'database') ||
            str_contains($value, 'mysql') ||
            in_array($value, ['db', 'database_lain', 'external_database'], true)
        ) {
            return 'database';
        }

        return 'excel';
    }

    private function transactionType(): string
    {
        $value = strtolower((string) ($this->state['transaction_type']
            ?? $this->state['jenis_transaksi']
            ?? $this->state['type']
            ?? 'outbound'));

        if (str_contains($value, 'inbound') || str_contains($value, 'masuk')) {
            return 'inbound';
        }

        return 'outbound';
    }

    private function sourceName(): string
    {
        return $this->sourceType() === 'database' ? 'import_database' : 'import_excel';
    }

    private function statusAfterImport(): string
    {
        return (string) ($this->state['status_after_import'] ?? 'approved');
    }

    private function userId(): int
    {
        return (int) ($this->state['user_id'] ?? 1);
    }

    private function selectedWarehouseId(): int
    {
        $warehouseId = (int) ($this->state['warehouse_id'] ?? 0);

        if ($warehouseId <= 0) {
            throw new \Exception('Gudang wajib dipilih.');
        }

        return $warehouseId;
    }

    private function importFromExternalDatabase(string $transactionType, ?InventoryImportLog $log = null): array
    {
        $warehouseId = $this->selectedWarehouseId();
        $connectionName = $this->configureExternalDatabaseConnection();
        $query = $this->externalDatabaseQuery($connectionName);

        $context = $this->newImportContext();
        $chunkSize = (int) env('INVENTORY_IMPORT_CHUNK_SIZE', 500);
        $chunkSize = max(100, min($chunkSize, 1000));

        if ($query instanceof Builder) {
            $orderColumn = $this->externalOrderColumn($connectionName, $query);

            if ($orderColumn) {
                $query->orderBy($orderColumn);
            }

            $query->chunk($chunkSize, function ($rows) use ($transactionType, $warehouseId, $log, &$context): void {
                foreach ($rows as $row) {
                    $this->processGenericRow($transactionType, $this->normalizeRecord((array) $row), $warehouseId, $context);
                }

                $this->updateProgressLog($log, $context);
            });
        } else {
            foreach ($query as $row) {
                $this->processGenericRow($transactionType, $this->normalizeRecord((array) $row), $warehouseId, $context);
            }

            $this->updateProgressLog($log, $context);
        }

        return $this->finishContext(
            $context,
            $transactionType === 'inbound'
                ? 'Import barang masuk dari database eksternal selesai.'
                : 'Import barang keluar dari database eksternal selesai.'
        );
    }

    private function configureExternalDatabaseConnection(): string
    {
        $host = (string) ($this->state['host']
            ?? $this->state['db_host']
            ?? $this->state['database_host']
            ?? '127.0.0.1');

        $port = (int) ($this->state['port']
            ?? $this->state['db_port']
            ?? $this->state['database_port']
            ?? 3306);

        $database = (string) ($this->state['database_name']
            ?? $this->state['db_name']
            ?? $this->state['database']
            ?? '');

        $username = (string) ($this->state['username']
            ?? $this->state['db_username']
            ?? $this->state['database_username']
            ?? '');

        $password = (string) ($this->state['password']
            ?? $this->state['db_password']
            ?? $this->state['database_password']
            ?? '');

        if ($database === '' || $username === '') {
            throw new \Exception('Database Name dan Username database eksternal wajib diisi.');
        }

        $connectionName = 'inventory_import_external';

        config([
            "database.connections.{$connectionName}" => [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => false,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? array_filter([
                    \PDO::ATTR_TIMEOUT => 30,
                ]) : [],
            ],
        ]);

        DB::purge($connectionName);
        DB::connection($connectionName)->getPdo();

        return $connectionName;
    }

    private function externalDatabaseQuery(string $connectionName): Builder|array
    {
        $sql = trim((string) ($this->state['sql_query']
            ?? $this->state['query_sql']
            ?? $this->state['query']
            ?? $this->state['custom_query']
            ?? ''));

        if ($sql !== '') {
            $this->validateSelectOnlySql($sql);

            return DB::connection($connectionName)->select($sql);
        }

        $table = trim((string) ($this->state['table_name']
            ?? $this->state['table_or_view']
            ?? $this->state['db_table']
            ?? $this->state['database_table']
            ?? $this->state['nama_tabel']
            ?? ''));

        if ($table === '') {
            throw new \Exception('Nama Tabel / View wajib diisi jika Query SQL Opsional kosong.');
        }

        if (! preg_match('/^[A-Za-z0-9_\.]+$/', $table)) {
            throw new \Exception('Nama tabel/view database eksternal tidak valid.');
        }

        return DB::connection($connectionName)->table($table);
    }

    private function validateSelectOnlySql(string $sql): void
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $sql)));

        if (! str_starts_with($normalized, 'select ')) {
            throw new \Exception('Query SQL Opsional hanya boleh menggunakan SELECT.');
        }

        $blockedKeywords = [
            ' insert ', ' update ', ' delete ', ' drop ', ' alter ', ' truncate ',
            ' create ', ' replace ', ' grant ', ' revoke ', ' rename ', ' call ',
            ';insert ', ';update ', ';delete ', ';drop ', ';alter ', ';truncate ', ';create ',
        ];

        foreach ($blockedKeywords as $keyword) {
            if (str_contains(' ' . $normalized . ' ', $keyword)) {
                throw new \Exception('Query SQL Opsional mengandung perintah yang tidak diizinkan.');
            }
        }
    }

    private function externalOrderColumn(string $connectionName, Builder $query): ?string
    {
        $from = $query->from;

        if (! is_string($from) || str_contains($from, ' ')) {
            return null;
        }

        try {
            $columns = collect(DB::connection($connectionName)->getSchemaBuilder()->getColumnListing($from));

            return $columns->contains('id') ? 'id' : ($columns->contains('tanggal') ? 'tanggal' : null);
        } catch (Throwable) {
            return null;
        }
    }

    private function listWorksheetInfo(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        if (method_exists($reader, 'listWorksheetInfo')) {
            return call_user_func([$reader, 'listWorksheetInfo'], $filePath);
        }

        $spreadsheet = $reader->load($filePath);
        $info = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $highestColumn = $worksheet->getHighestColumn();
            $totalColumns = Coordinate::columnIndexFromString($highestColumn);

            $info[] = [
                'worksheetName' => $worksheet->getTitle(),
                'totalRows' => $worksheet->getHighestRow(),
                'totalColumns' => $totalColumns,
                'lastColumnLetter' => $highestColumn,
            ];
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $reader);
        gc_collect_cycles();

        return $info;
    }

    private function worksheetRowsToArrayWithCachedFormulas($sheet, int $highestRow, int $totalColumns): array
{
    $rows = [];

    for ($row = 1; $row <= $highestRow; $row++) {
        $values = [];

        for ($column = 1; $column <= $totalColumns; $column++) {
            $cellAddress = Coordinate::stringFromColumnIndex($column) . $row;
            $cell = $sheet->getCell($cellAddress);

            $values[] = $this->spreadsheetCellValue($cell);
        }

        $rows[] = $values;
    }

    return $rows;
}

private function spreadsheetCellValue($cell): mixed
{
    $value = null;

    try {
        $value = $cell->getCalculatedValue();
    } catch (Throwable) {
        $value = null;
    }

    if ($this->isInvalidSpreadsheetValue($value)) {
        try {
            $oldCalculatedValue = $cell->getOldCalculatedValue();

            if (! $this->isInvalidSpreadsheetValue($oldCalculatedValue)) {
                return $oldCalculatedValue;
            }
        } catch (Throwable) {
            // Abaikan, lanjut fallback ke raw value.
        }
    }

    if (! $this->isInvalidSpreadsheetValue($value)) {
        return $value;
    }

    try {
        return $cell->getValue();
    } catch (Throwable) {
        return null;
    }
}

private function isInvalidSpreadsheetValue(mixed $value): bool
{
    if ($value === null) {
        return true;
    }

    if (is_string($value)) {
        $value = trim($value);

        return $value === '' || str_starts_with($value, '#');
    }

    return false;
}

    private function importInboundFlexibleExcel(string $filePath, ?InventoryImportLog $log = null): array
    {
        $worksheetInfo = $this->listWorksheetInfo($filePath);

        if (empty($worksheetInfo)) {
            throw new \Exception('File Excel kosong.');
        }

        $warehouseId = $this->selectedWarehouseId();
        $context = $this->newImportContext();

        foreach ($worksheetInfo as $sheetInfo) {
            $sheetName = (string) ($sheetInfo['worksheetName'] ?? '');

            if ($this->shouldSkipFlexibleSheet($sheetName)) {
                continue;
            }

            $highestRow = (int) ($sheetInfo['totalRows'] ?? 0);
            $totalColumns = max((int) ($sheetInfo['totalColumns'] ?? 30), 30);
            $highestColumn = Coordinate::stringFromColumnIndex($totalColumns);

            if ($highestRow <= 0) {
                continue;
            }

            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly([$sheetName]);

            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getSheetByName($sheetName);

            if (! $sheet) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $reader);
                continue;
            }

            $rows = $this->worksheetRowsToArrayWithCachedFormulas($sheet, $highestRow, $totalColumns);

            $headerInfo = $this->findFlexibleInboundHeader($rows);

            if (! $headerInfo) {
                $this->skipRow($context, "Sheet {$sheetName} dilewati: header tidak ditemukan.");

                $spreadsheet->disconnectWorksheets();
                unset($rows, $sheet, $spreadsheet, $reader);
                gc_collect_cycles();

                continue;
            }

            $supplierName = $this->detectFlexibleSupplierName($rows, $sheetName, $filePath);

            $currentDate = null;
            $currentInvoice = null;
            $currentSupplierName = $supplierName;

            for ($index = $headerInfo['row_index'] + 1; $index < count($rows); $index++) {
                $rowNumber = $index + 1;

                $values = collect($rows[$index])
                    ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                    ->values();

                if ($this->isEmptyExcelRow($values)) {
                    continue;
                }

                $context['total_rows']++;

                if ($this->isFlexibleSummaryRow($values, $headerInfo)) {
                    continue;
                }

                $record = $this->makeFlexibleInboundRecord(
                    values: $values,
                    headerInfo: $headerInfo,
                    defaultSupplierName: $supplierName,
                    sheetName: $sheetName,
                    rowNumber: $rowNumber,
                    currentDate: $currentDate,
                    currentInvoice: $currentInvoice,
                    currentSupplierName: $currentSupplierName,
                    context: $context
                );

                if (! $record) {
                    continue;
                }

                $this->processGenericRow(
                    transactionType: 'inbound',
                    record: $this->normalizeRecord($record),
                    warehouseId: $warehouseId,
                    context: $context,
                    countRow: false
                );
            }

            $this->updateProgressLog($log, $context);

            $spreadsheet->disconnectWorksheets();
            unset($rows, $sheet, $spreadsheet, $reader);
            gc_collect_cycles();
        }

        return $this->finishContext($context, 'Import barang masuk dari Excel selesai.');
    }

    private function shouldSkipFlexibleSheet(string $sheetName): bool
    {
        $name = strtoupper(trim($sheetName));

        return in_array($name, [
            'TARIF',
            'T.BARU',
            'H.BELI',
            'HARGA',
            'PRICE',
            'SETTING',
            'CONFIG',
            'MASTER',
        ], true);
    }

    private function findFlexibleInboundHeader(array $rows): ?array
    {
        $bestHeader = null;
        $bestScore = 0;

        foreach ($rows as $rowIndex => $row) {
            $values = collect($row)
                ->map(fn ($value) => $this->normalizeExcelHeader((string) $value))
                ->values();

            $mapping = $this->mapFlexibleInboundColumns($values);

            $score = 0;

            foreach (['tanggal', 'no_invoice', 'kode_barang', 'nama_barang', 'qty', 'harga', 'subtotal'] as $key) {
                if (array_key_exists($key, $mapping)) {
                    $score++;
                }
            }

            $hasDate = array_key_exists('tanggal', $mapping);
            $hasInvoice = array_key_exists('no_invoice', $mapping);
            $hasQty = array_key_exists('qty', $mapping);
            $hasProductSource = array_key_exists('nama_barang', $mapping) || array_key_exists('kode_barang', $mapping);
            $hasAmountSource = array_key_exists('harga', $mapping) || array_key_exists('subtotal', $mapping);

            if ($hasDate && $hasInvoice && $hasQty && $hasProductSource && $hasAmountSource && $score > $bestScore) {
                $bestScore = $score;
                $bestHeader = [
                    'row_index' => $rowIndex,
                    'mapping' => $mapping,
                    'header_values' => $values->all(),
                ];
            }
        }

        return $bestHeader;
    }

    private function mapFlexibleInboundColumns(Collection $headers): array
    {
        $aliases = $this->flexibleInboundColumnAliases();
        $mapping = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            foreach ($aliases as $standardKey => $aliasList) {
                foreach ($aliasList as $alias) {
                    $normalizedAlias = $this->normalizeExcelHeader($alias);

                    if ($header === $normalizedAlias) {
                        $mapping[$standardKey] = (int) $index;
                        continue 3;
                    }
                }
            }
        }

        return $mapping;
    }

    private function flexibleInboundColumnAliases(): array
    {
        return [
            'tanggal' => [
                'tanggal',
                'tgl',
                'date',
                'transaction_date',
                'tgl_transaksi',
            ],

            'no_invoice' => [
                'no_invoice',
                'invoice',
                'invoice_number',
                'nomor_invoice',
                'reference_number',
                'no_sj',
                'sj',
                's_jalan',
                's. jalan',
                'surat_jalan',
                'no_surat_jalan',
                'no_po',
                'po',
            ],

            'nama_supplier' => [
                'supplier',
                'nama_supplier',
                'vendor',
                'vendor_name',
                'pemasok',
                'nama_pemasok',
            ],

            'kode_barang' => [
                'kode',
                'kode_barang',
                'code',
                'product_code',
                'sku',
                'item_code',
                'kode_produk',
            ],

            'nama_barang' => [
                'nama_barang',
                'barang',
                'produk',
                'product',
                'product_name',
                'item_name',
                'nama_produk',
                'description',
                'deskripsi',
            ],

            'ukuran' => [
                'ukuran',
                'size',
                'dimensi',
                'dimension',
                'panjang',
                'lebar',
                'tinggi',
            ],

            'qty' => [
                'qty',
                'quantity',
                'jumlah_barang',
                'pcs',
            ],

            'm3' => [
                'm3',
                'm³',
                'kubikasi',
                'volume',
            ],

            'harga' => [
                'harga',
                'harga_satuan',
                'harga_beli',
                'unit_cost',
                'unit_price',
                'price',
                'cost',
                'rate',
            ],

            'subtotal' => [
                'jumlah',
                'subtotal',
                'sub_total',
                'total',
                'amount',
                'nilai',
                'total_harga',
                'jumlah_rp',
            ],

            'keterangan' => [
                'keterangan',
                'note',
                'notes',
                'remark',
                'remarks',
            ],
        ];
    }

    private function normalizeExcelHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['.', '/', '\\', '-', ' '], '_', $value);
        $value = preg_replace('/[^a-z0-9_]/', '', $value);
        $value = preg_replace('/_+/', '_', (string) $value);

        return trim((string) $value, '_');
    }

    private function makeFlexibleInboundRecord(
    Collection $values,
    array $headerInfo,
    string $defaultSupplierName,
    string $sheetName,
    int $rowNumber,
    ?Carbon &$currentDate,
    ?string &$currentInvoice,
    ?string &$currentSupplierName,
    array &$context
): ?array {
    $mapping = $headerInfo['mapping'];

    $date = $this->mappedDateValue($values, $mapping, 'tanggal');

    if ($date) {
        $currentDate = $date;
    }

    $supplierName = $this->mappedStringValue($values, $mapping, 'nama_supplier');

    if ($supplierName) {
        $currentSupplierName = $supplierName;
    }

    if (! $currentSupplierName) {
        $currentSupplierName = $defaultSupplierName;
    }

    $invoice = $this->buildFlexibleInvoiceNumber($values, $mapping, $currentSupplierName);

    if ($invoice) {
        $currentInvoice = $invoice;
    }

    if (! $currentDate) {
        $this->skipRow($context, "Baris {$rowNumber} sheet {$sheetName} dilewati: tanggal tidak valid.");
        return null;
    }

    if (! $currentInvoice) {
        $this->skipRow($context, "Baris {$rowNumber} sheet {$sheetName} dilewati: nomor invoice/surat jalan tidak ditemukan.");
        return null;
    }

    $productName = $this->buildFlexibleInboundProductName($values, $mapping);

    if (! $productName) {
        $this->skipRow($context, "Baris {$rowNumber} sheet {$sheetName} dilewati: nama/kode barang kosong.");
        return null;
    }

    if ($this->isNonStockProductLine($productName)) {
        return null;
    }

    $qtyRaw = $this->mappedRawValue($values, $mapping, 'qty');
    $m3Raw = $this->mappedRawValue($values, $mapping, 'm3');

    $moneyValues = $this->inferFlexibleInboundMoneyValues($values, $mapping);

    $qtyNumber = $this->toNumber($qtyRaw);
    $volumeM3 = $this->toNumber($m3Raw);
    $pricePerM3 = $this->toNumber($moneyValues['harga'] ?? null);
    $excelSubtotal = $this->toNumber($moneyValues['subtotal'] ?? null);

    if ($qtyNumber <= 0) {
        $this->skipRow($context, "Baris {$rowNumber} sheet {$sheetName} dilewati: qty kosong/tidak valid.");
        return null;
    }

    /*
     * Kalau kolom JUMLAH Excel kosong, fallback:
     * M3 x HARGA/M3.
     */
    if ($excelSubtotal <= 0 && $volumeM3 > 0 && $pricePerM3 > 0) {
        $excelSubtotal = $volumeM3 * $pricePerM3;
    }

    /*
     * Fallback terakhir untuk format umum:
     * QTY x HARGA.
     */
    if ($excelSubtotal <= 0 && $qtyNumber > 0 && $pricePerM3 > 0) {
        $excelSubtotal = $qtyNumber * $pricePerM3;
    }

    $scaledMoney = $this->normalizeInboundMoneyScale(
        harga: $pricePerM3,
        subtotal: $excelSubtotal,
        supplierName: $currentSupplierName ?: $defaultSupplierName
    );

    $pricePerM3 = $scaledMoney['harga'];
    $excelSubtotal = $scaledMoney['subtotal'];

    /*
     * Harga sistem inventory:
     * unit_cost = JUMLAH Excel / QTY
     */
    $unitCost = $excelSubtotal > 0 && $qtyNumber > 0
        ? round($excelSubtotal / $qtyNumber, 2)
        : 0;

    $keterangan = $this->mappedStringValue($values, $mapping, 'keterangan');

    $noteParts = [];

    if ($keterangan) {
        $noteParts[] = $keterangan;
    }

    $noteParts[] = 'Import barang masuk dari sheet ' . $sheetName . ', baris ' . $rowNumber;

    if ($volumeM3 > 0) {
        $noteParts[] = 'M3 Excel: ' . $this->formatImportedNumberForNote($volumeM3);
    }

    if ($pricePerM3 > 0) {
        $noteParts[] = 'Harga/M3 Excel: ' . $this->formatImportedNumberForNote($pricePerM3);
    }

    if ($excelSubtotal > 0) {
        $noteParts[] = 'Jumlah Excel: ' . $this->formatImportedNumberForNote($excelSubtotal);
    }

    /*
     * Debug sementara.
     * Kalau Harga/M3 atau Jumlah Excel masih kosong,
     * nanti Catatan Item akan menampilkan isi kolom setelah M3.
     */
    if (($pricePerM3 <= 0 || $excelSubtotal <= 0) && ! empty($moneyValues['debug'])) {
        $noteParts[] = 'DEBUG setelah M3: ' . $moneyValues['debug'];
    }

    return [
        'tanggal' => $currentDate->toDateString(),
        'no_invoice' => $currentInvoice,
        'nama_supplier' => $currentSupplierName ?: $defaultSupplierName,
        'nama_barang' => $productName,

        // Data utama sistem inventory
        'qty' => $qtyNumber,
        'harga' => $unitCost,
        'subtotal' => $excelSubtotal,

        // Data asli Excel QNT/VITA
        'volume_m3' => $volumeM3 > 0 ? $volumeM3 : null,
        'price_per_m3' => $pricePerM3 > 0 ? $pricePerM3 : null,
        'excel_subtotal' => $excelSubtotal > 0 ? $excelSubtotal : null,

        'status' => $this->statusAfterImport(),
        'keterangan' => implode(' | ', $noteParts),
    ];
}

    private function normalizeInboundMoneyScale(float $harga, float $subtotal, string $supplierName): array
    {
        $multiplier = $this->inboundMoneyMultiplier($harga, $subtotal, $supplierName);

        return [
            'harga' => round($harga * $multiplier, 2),
            'subtotal' => round($subtotal * $multiplier, 2),
        ];
    }

    private function inboundMoneyMultiplier(float $harga, float $subtotal, string $supplierName): float
{
    $manualMultiplier = $this->state['money_multiplier']
        ?? $this->state['nominal_multiplier']
        ?? $this->state['price_multiplier']
        ?? null;

    if (is_numeric($manualMultiplier) && (float) $manualMultiplier > 0) {
        return (float) $manualMultiplier;
    }

    return 1;
}

    private function formatImportedNumberForNote(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    private function inferFlexibleInboundMoneyValues(Collection $values, array $mapping): array
{
    $qtyIndex = array_key_exists('qty', $mapping) ? (int) $mapping['qty'] : null;
    $m3Index = array_key_exists('m3', $mapping) ? (int) $mapping['m3'] : null;

    /*
     * PRIORITAS QNT/VITA:
     *
     * Format Excel:
     * QTY | M3 | Rp | HARGA | Rp | JUMLAH
     *
     * Kalau kolom M3 ditemukan:
     * - angka valid pertama setelah M3 = HARGA/M3
     * - angka valid kedua setelah M3   = JUMLAH Excel
     */
    if ($m3Index !== null) {
        $numericCandidates = $this->numericCandidatesFromRow(
            values: $values,
            startIndex: $m3Index + 1,
            maxLookAhead: 20
        );

        return [
            'harga' => $numericCandidates[0]['raw'] ?? null,
            'subtotal' => $numericCandidates[1]['raw'] ?? null,
            'debug' => $this->debugRowSlice($values, $m3Index + 1, 20),
        ];
    }

    /*
     * Fallback format umum:
     * QTY | HARGA | SUBTOTAL
     */
    $harga = $this->mappedRawValue($values, $mapping, 'harga');
    $subtotal = $this->mappedRawValue($values, $mapping, 'subtotal');

    $hargaNumber = $this->toNumber($harga);
    $subtotalNumber = $this->toNumber($subtotal);

    if ($hargaNumber > 0 && $subtotalNumber > 0) {
        return [
            'harga' => $harga,
            'subtotal' => $subtotal,
            'debug' => '',
        ];
    }

    if ($qtyIndex !== null) {
        $numericCandidates = $this->numericCandidatesFromRow(
            values: $values,
            startIndex: $qtyIndex + 1,
            maxLookAhead: 20
        );

        if ($hargaNumber <= 0 && count($numericCandidates) >= 1) {
            $harga = $numericCandidates[0]['raw'];
        }

        if ($subtotalNumber <= 0 && count($numericCandidates) >= 2) {
            $subtotal = $numericCandidates[1]['raw'];
        }

        return [
            'harga' => $harga,
            'subtotal' => $subtotal,
            'debug' => $this->debugRowSlice($values, $qtyIndex + 1, 20),
        ];
    }

    return [
        'harga' => $harga,
        'subtotal' => $subtotal,
        'debug' => '',
    ];
}

private function numericCandidatesFromRow(Collection $values, int $startIndex, int $maxLookAhead = 20): array
{
    $numericCandidates = [];
    $endIndex = min($values->count() - 1, $startIndex + $maxLookAhead);

    for ($index = $startIndex; $index <= $endIndex; $index++) {
        $candidate = $values->get($index);

        if ($candidate === null || trim((string) $candidate) === '') {
            continue;
        }

        $number = $this->toNumber($candidate);

        if ($number <= 0) {
            continue;
        }

        $numericCandidates[] = [
            'raw' => $candidate,
            'number' => $number,
            'index' => $index,
        ];

        if (count($numericCandidates) >= 2) {
            break;
        }
    }

    return $numericCandidates;
}

private function debugRowSlice(Collection $values, int $startIndex, int $maxLookAhead = 20): string
{
    $parts = [];
    $endIndex = min($values->count() - 1, $startIndex + $maxLookAhead);

    for ($index = $startIndex; $index <= $endIndex; $index++) {
        $value = $values->get($index);

        if ($value === null || trim((string) $value) === '') {
            continue;
        }

        if ($value instanceof \DateTimeInterface) {
            $text = $value->format('Y-m-d H:i:s');
        } else {
            $text = trim((string) $value);
        }

        if ($text === '') {
            continue;
        }

        $parts[] = $index . '=' . $text;
    }

    return implode(' | ', $parts);
}

    private function mappedRawValue(Collection $values, array $mapping, string $key): mixed
    {
        if (! array_key_exists($key, $mapping)) {
            return null;
        }

        return $values->get((int) $mapping[$key]);
    }

    private function mappedStringValue(Collection $values, array $mapping, string $key): ?string
    {
        $value = $this->mappedRawValue($values, $mapping, $key);

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function mappedDateValue(Collection $values, array $mapping, string $key): ?Carbon
    {
        return $this->toDate($this->mappedRawValue($values, $mapping, $key));
    }

    private function buildFlexibleInvoiceNumber(Collection $values, array $mapping, string $supplierName): ?string
    {
        if (! array_key_exists('no_invoice', $mapping)) {
            return null;
        }

        $invoiceIndex = (int) $mapping['no_invoice'];
        $invoiceParts = [];

        $endIndex = $invoiceIndex;

        if (array_key_exists('kode_barang', $mapping)) {
            $endIndex = max($invoiceIndex, ((int) $mapping['kode_barang']) - 1);
        }

        for ($index = $invoiceIndex; $index <= $endIndex; $index++) {
            $value = $values->get($index);

            if ($value === null || trim((string) $value) === '') {
                continue;
            }

            $text = trim((string) $value);

            if (is_numeric($value) && (float) $value === floor((float) $value)) {
                $text = (string) ((int) $value);
            }

            $invoiceParts[] = $text;
        }

        if (empty($invoiceParts)) {
            return null;
        }

        $rawInvoice = implode('-', $invoiceParts);
        $rawInvoice = preg_replace('/-+/', '-', $rawInvoice);
        $rawInvoice = trim((string) $rawInvoice, '-');

        if ($rawInvoice === '') {
            return null;
        }

        return $this->supplierInvoicePrefix($supplierName) . '-' . $rawInvoice;
    }

    private function buildFlexibleInboundProductName(Collection $values, array $mapping): ?string
    {
        $name = $this->mappedStringValue($values, $mapping, 'nama_barang');

        if ($name) {
            return $this->cleanImportedProductName($name);
        }

        $kode = $this->mappedStringValue($values, $mapping, 'kode_barang');

        if (! $kode) {
            return null;
        }

        $kode = $this->cleanImportedProductName($kode);

        $dimensionParts = [];

        if (array_key_exists('ukuran', $mapping)) {
            $start = (int) $mapping['ukuran'];
            $end = array_key_exists('qty', $mapping)
                ? ((int) $mapping['qty']) - 1
                : $start;

            for ($index = $start; $index <= $end; $index++) {
                $value = $values->get($index);

                if ($value === null || trim((string) $value) === '') {
                    continue;
                }

                $dimensionParts[] = $this->cleanFlexibleDimensionPart($value);
            }
        } elseif (array_key_exists('kode_barang', $mapping) && array_key_exists('qty', $mapping)) {
            $start = ((int) $mapping['kode_barang']) + 1;
            $end = ((int) $mapping['qty']) - 1;

            for ($index = $start; $index <= $end; $index++) {
                $value = $values->get($index);

                if ($value === null || trim((string) $value) === '') {
                    continue;
                }

                $dimensionParts[] = $this->cleanFlexibleDimensionPart($value);
            }
        }

        $dimensionParts = array_values(array_filter($dimensionParts));

        if (empty($dimensionParts)) {
            return $kode;
        }

        return trim($kode . ' ' . implode('X', $dimensionParts));
    }

    private function cleanFlexibleDimensionPart(mixed $value): string
    {
        if (is_numeric($value)) {
            $number = (float) $value;

            return floor($number) == $number
                ? (string) ((int) $number)
                : rtrim(rtrim((string) $number, '0'), '.');
        }

        $text = strtoupper(trim((string) $value));

        if (in_array($text, ['X', '×', '*'], true)) {
            return '';
        }

        return $text;
    }

    private function isFlexibleSummaryRow(Collection $values, array $headerInfo): bool
    {
        $mapping = $headerInfo['mapping'];

        $texts = $values
            ->map(fn ($value) => strtoupper(trim((string) $value)))
            ->filter()
            ->values();

        if ($texts->isEmpty()) {
            return true;
        }

        $joined = $texts->implode(' ');

        if (
            str_contains($joined, 'TOTAL') ||
            str_contains($joined, 'GRAND TOTAL') ||
            str_contains($joined, 'JUMLAH TOTAL')
        ) {
            return true;
        }

        $productText = null;

        if (array_key_exists('nama_barang', $mapping)) {
            $productText = strtoupper((string) $values->get((int) $mapping['nama_barang']));
        } elseif (array_key_exists('kode_barang', $mapping)) {
            $productText = strtoupper((string) $values->get((int) $mapping['kode_barang']));
        }

        return in_array(trim((string) $productText), [
            '',
            'KODE',
            'NAMA BARANG',
            'NAMA PRODUK',
            'TOTAL',
            'JUMLAH',
        ], true);
    }

    private function detectFlexibleSupplierName(array $rows, string $sheetName, string $filePath): string
    {
        foreach (array_slice($rows, 0, 10) as $row) {
            foreach ($row as $value) {
                $text = trim((string) $value);

                if ($text === '') {
                    continue;
                }

                $upper = strtoupper($text);

                if (str_starts_with($upper, 'PT ')) {
                    return $text;
                }

                if (str_starts_with($upper, 'PT.')) {
                    return $text;
                }

                if (str_contains($upper, 'QUANTUM')) {
                    return 'PT. QUANTUM TOSAN INTERNASIONAL';
                }

                if (str_contains($upper, 'TRI SUKSES')) {
                    return 'PT. TRI SUKSES JAYA';
                }

                if (str_contains($upper, 'VITA')) {
                    return 'PT. TRI SUKSES JAYA';
                }
            }
        }

        $filename = strtoupper(pathinfo($filePath, \PATHINFO_FILENAME));
        $upperSheet = strtoupper($sheetName);

        if (str_contains($filename, 'QNT') || str_contains($upperSheet, 'QNT')) {
            return 'PT. QUANTUM TOSAN INTERNASIONAL';
        }

        if (str_contains($filename, 'VITA') || str_contains($upperSheet, 'VITA')) {
            return 'PT. TRI SUKSES JAYA';
        }

        return 'Supplier Import Excel';
    }

    private function supplierInvoicePrefix(string $supplierName): string
    {
        $upper = strtoupper($supplierName);

        if (str_contains($upper, 'QUANTUM')) {
            return 'QNT';
        }

        if (str_contains($upper, 'TRI SUKSES') || str_contains($upper, 'VITA')) {
            return 'VITA';
        }

        return 'SUP';
    }

    private function importOutboundHistoricalExcel(string $filePath, array $state, ?InventoryImportLog $log = null): array
    {
        $worksheetInfo = $this->listWorksheetInfo($filePath);

        if (empty($worksheetInfo)) {
            throw new \Exception('File Excel kosong.');
        }

        $warehouseId = $this->selectedWarehouseId();
        $context = $this->newImportContext();

        $chunkSize = (int) env('INVENTORY_IMPORT_CHUNK_SIZE', 500);
        $chunkSize = max(100, min($chunkSize, 1000));

        foreach ($worksheetInfo as $sheetInfo) {
            $sheetName = $sheetInfo['worksheetName'] ?? null;
            $highestRow = (int) ($sheetInfo['totalRows'] ?? 0);
            $totalColumns = max((int) ($sheetInfo['totalColumns'] ?? 42), 42);
            $highestColumn = Coordinate::stringFromColumnIndex($totalColumns);

            $currentDate = null;
            $currentInvoice = null;
            $currentCustomerName = null;

            if ($highestRow <= 0) {
                continue;
            }

            for ($startRow = 1; $startRow <= $highestRow; $startRow += $chunkSize) {
                $endRow = min($startRow + $chunkSize - 1, $highestRow);

                $reader = IOFactory::createReaderForFile($filePath);
                $reader->setReadDataOnly(true);

                if ($sheetName) {
                    $reader->setLoadSheetsOnly([$sheetName]);
                }

                $reader->setReadFilter(new \App\Jobs\ExcelChunkReadFilter($startRow, $endRow));

                $spreadsheet = $reader->load($filePath);
                $sheet = $sheetName ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getActiveSheet();

                if (! $sheet) {
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet, $reader);
                    continue;
                }

                $rows = $sheet->rangeToArray("A{$startRow}:{$highestColumn}{$endRow}", null, true, false);

                foreach ($rows as $offset => $row) {
                    $rowNumber = $startRow + $offset;
                    $values = collect($row)->map(fn ($value) => is_string($value) ? trim($value) : $value)->values();

                    if ($this->isEmptyExcelRow($values)) {
                        continue;
                    }

                    $context['total_rows']++;

                    if (
                        $this->isOutboundHeaderRow($values) ||
                        $this->isOutboundTotalRow($values) ||
                        $this->isOutboundGrandTotalRow($values)
                    ) {
                        continue;
                    }

                    $dateCell = $values->get(1);
                    $invoiceCell = $this->cell($values, 2);
                    $customerCell = $this->cell($values, 3);

                    if ($invoiceCell) {
                        $currentInvoice = $invoiceCell;
                        $currentDate = $this->toDate($dateCell);

                        if ($customerCell) {
                            $currentCustomerName = $customerCell;
                        }
                    }

                    if (! $currentInvoice) {
                        $this->skipRow($context, "Baris {$rowNumber} dilewati: nomor invoice tidak ditemukan.");
                        continue;
                    }

                    if (! $currentDate) {
                        $this->skipRow($context, "Baris {$rowNumber} dilewati: tanggal transaksi tidak valid.");
                        continue;
                    }

                    $productName = $this->cell($values, 4);

                    if (! $productName || strtoupper($productName) === 'TOTAL') {
                        continue;
                    }

                    $record = [
                        'tanggal' => $currentDate->toDateString(),
                        'no_invoice' => $currentInvoice,
                        'nama_customer' => $currentCustomerName,
                        'nama_barang' => $productName,
                        'qty' => $values->get(37),
                        'harga' => $values->get(38),
                        'subtotal' => $values->get(39),
                        'sales_name' => 'Admin',
                        'status' => $this->statusAfterImport(),
                        'keterangan' => $this->cell($values, 41),
                    ];

                    $this->processGenericRow('outbound', $this->normalizeRecord($record), $warehouseId, $context, false);
                }

                $this->updateProgressLog($log, $context);

                $spreadsheet->disconnectWorksheets();
                unset($rows, $sheet, $spreadsheet, $reader);
                gc_collect_cycles();
            }
        }

        return $this->finishContext($context, 'Import barang keluar dari Excel selesai.');
    }

    private function processGenericRow(string $transactionType, array $record, int $warehouseId, array &$context, bool $countRow = true): void
    {
        if ($countRow) {
            $context['total_rows']++;
        }

        $date = $this->toDate($this->recordValue($record, ['tanggal', 'transaction_date', 'date', 'tgl', 'tgl_transaksi']));
        $invoice = $this->stringValue($this->recordValue($record, ['no_invoice', 'invoice_number', 'reference_number', 'invoice', 'nomor_invoice']));
        $productName = $this->stringValue($this->recordValue($record, ['nama_barang', 'product_name', 'name_barang', 'barang', 'produk', 'item_name', 'product']));
        $qty = $this->toNumber($this->recordValue($record, ['qty', 'quantity', 'jumlah', 'jumlah_barang']));
        $unitPrice = $this->toNumber($this->recordValue($record, ['harga', 'unit_price', 'price', 'harga_satuan']));
        $subtotal = $this->toNumber($this->recordValue($record, ['subtotal', 'sub_total', 'total', 'amount']));
        $note = $this->stringValue($this->recordValue($record, ['keterangan', 'note', 'notes', 'remark', 'remarks']));

        $volumeM3 = $this->toNumber($this->recordValue($record, ['volume_m3']));
        $pricePerM3 = $this->toNumber($this->recordValue($record, ['price_per_m3']));
        $excelSubtotal = $this->toNumber($this->recordValue($record, ['excel_subtotal']));

        if (! $date) {
            $this->skipRow($context, 'Baris dilewati: tanggal transaksi tidak valid.');
            return;
        }

        if (! $invoice) {
            $this->skipRow($context, 'Baris dilewati: nomor invoice tidak ditemukan.');
            return;
        }

        if (! $productName) {
            $this->skipRow($context, "Baris invoice {$invoice} dilewati: nama barang kosong.");
            return;
        }

        if ($this->isNonStockProductLine($productName)) {
            $this->skipRow($context, "Invoice {$invoice} dilewati: '{$productName}' dianggap bukan barang stok.");
            return;
        }

        if ($qty <= 0) {
            $this->skipRow($context, "Baris invoice {$invoice} dilewati: qty kosong/tidak valid.");
            return;
        }

        if ($subtotal <= 0 && $unitPrice > 0) {
            $subtotal = $qty * $unitPrice;
        }

        $product = $this->findProductByExcelName($productName);

        if (! $product) {
            $this->skipRow($context, "Invoice {$invoice} dilewati: produk '{$productName}' gagal dibuat otomatis.");
            return;
        }

        if ($transactionType === 'inbound') {
            $supplierName = $this->stringValue($this->recordValue($record, ['nama_supplier', 'supplier_name', 'supplier', 'vendor', 'vendor_name']));

            $this->storeInboundRow(
                date: $date,
                invoice: $invoice,
                supplierName: $supplierName,
                product: $product,
                qty: $qty,
                unitPrice: $unitPrice,
                subtotal: $subtotal,
                warehouseId: $warehouseId,
                note: $note,
                context: $context,
                volumeM3: $volumeM3 > 0 ? $volumeM3 : null,
                pricePerM3: $pricePerM3 > 0 ? $pricePerM3 : null,
                excelSubtotal: $excelSubtotal > 0 ? $excelSubtotal : null,
            );
        } else {
            $customerName = $this->stringValue($this->recordValue($record, ['nama_customer', 'customer_name', 'customer', 'pelanggan', 'tujuan']));
            $salesName = $this->stringValue($this->recordValue($record, ['sales_name', 'sales', 'admin'])) ?: 'Admin';

            $this->storeOutboundRow(
                date: $date,
                invoice: $invoice,
                customerName: $customerName,
                salesName: $salesName,
                product: $product,
                qty: $qty,
                unitPrice: $unitPrice,
                subtotal: $subtotal,
                warehouseId: $warehouseId,
                note: $note,
                context: $context
            );
        }
    }

    private function storeInboundRow(
        Carbon $date,
        string $invoice,
        ?string $supplierName,
        Product $product,
        float $qty,
        float $unitPrice,
        float $subtotal,
        int $warehouseId,
        ?string $note,
        array &$context,
        ?float $volumeM3 = null,
        ?float $pricePerM3 = null,
        ?float $excelSubtotal = null,
    ): void {
        $source = $this->sourceName();
        $duplicateKey = 'inbound:' . $source . ':' . $invoice;

        if (! array_key_exists($duplicateKey, $context['duplicate_invoice_cache'])) {
            $context['duplicate_invoice_cache'][$duplicateKey] = InboundTransaction::query()
                ->where('invoice_number', $invoice)
                ->where('source', $source)
                ->exists();
        }

        if ($context['duplicate_invoice_cache'][$duplicateKey]) {
            $this->skipRow($context, "Invoice {$invoice} dilewati: sudah pernah diimport.");
            return;
        }

        $supplier = $this->findOrCreateSupplier($supplierName);
        $transactionCacheKey = 'inbound:' . $invoice;

        if (! isset($context['transaction_id_cache'][$transactionCacheKey])) {
            $transaction = InboundTransaction::create($this->filterColumns('inbound_transactions', [
                'transaction_number' => $this->nextInboundImportTransactionNumber($date),
                'transaction_date' => $date->toDateString(),
                'inbound_type' => 'pembelian',
                'invoice_number' => $invoice,
                'reference_number' => $invoice,
                'supplier_id' => $supplier?->id,
                'warehouse_id' => $warehouseId,
                'note' => 'Import barang masuk. Invoice: ' . $invoice,
                'status' => $this->statusAfterImport(),
                'sub_total' => 0,
                'discount_amount' => 0,
                'vat_percent' => 0,
                'vat_amount' => 0,
                'other_cost' => 0,
                'grand_total' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'submitted_by' => $this->userId(),
                'submitted_at' => now(),
                'approved_by' => $this->statusAfterImport() === 'approved' ? $this->userId() : null,
                'approved_at' => $this->statusAfterImport() === 'approved' ? now() : null,
                'approval_note' => $this->statusAfterImport() === 'approved'
                    ? 'Auto approved dari proses import.'
                    : null,
                'source' => $source,
            ]));

            $context['transaction_id_cache'][$transactionCacheKey] = $transaction->id;
            $context['created_transactions']++;
        }

        $transactionId = $context['transaction_id_cache'][$transactionCacheKey];

        $item = InboundTransactionItem::create($this->filterColumns('inbound_transaction_items', [
            'inbound_transaction_id' => $transactionId,
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'unit_id' => $this->getProductValue($product, ['unit_id']),
            'qty' => $qty,
            'quantity' => $qty,
            'unit_cost' => $unitPrice,
            'unit_price' => $unitPrice,
            'price' => $unitPrice,
            'discount_amount' => 0,
            'subtotal' => $subtotal,
            'sub_total' => $subtotal,
            'volume_m3' => $volumeM3,
            'price_per_m3' => $pricePerM3,
            'excel_subtotal' => $excelSubtotal,
            'stock_before_submit' => 0,
            'stock_after_submit' => 0,
            'product_code_snapshot' => $this->getProductValue($product, ['product_code', 'code', 'sku']),
            'product_name_snapshot' => $this->getProductValue($product, ['name', 'product_name']) ?: $product->getAttribute('name'),
            'unit_name_snapshot' => $this->getProductUnitName($product),
            'note' => $note,
        ]));

        $this->incrementTransactionTotals('inbound_transactions', $transactionId, $subtotal);

        if ($this->shouldUpdateOperationalStock()) {
            $stockResult = $this->applyStockMovement(
                productId: (int) $product->id,
                warehouseId: $warehouseId,
                qty: $qty,
                movementType: 'in',
                referenceType: 'inbound_transaction',
                referenceId: $transactionId,
                description: 'Import barang masuk. Invoice: ' . $invoice,
                movementDate: $date,
            );

            DB::table('inbound_transaction_items')
                ->where('id', $item->id)
                ->update($this->filterColumns('inbound_transaction_items', [
                    'stock_before_submit' => $stockResult['stock_before'],
                    'stock_after_submit' => $stockResult['stock_after'],
                    'updated_at' => now(),
                ]));
        }

        $context['imported_rows']++;
    }

    private function storeOutboundRow(
        Carbon $date,
        string $invoice,
        ?string $customerName,
        string $salesName,
        Product $product,
        float $qty,
        float $unitPrice,
        float $subtotal,
        int $warehouseId,
        ?string $note,
        array &$context
    ): void {
        $source = $this->sourceName();
        $duplicateKey = 'outbound:' . $source . ':' . $invoice;

        if (! array_key_exists($duplicateKey, $context['duplicate_invoice_cache'])) {
            $context['duplicate_invoice_cache'][$duplicateKey] = OutboundTransaction::query()
                ->where('reference_number', $invoice)
                ->where('source', $source)
                ->exists();
        }

        if ($context['duplicate_invoice_cache'][$duplicateKey]) {
            $this->skipRow($context, "Invoice {$invoice} dilewati: sudah pernah diimport.");
            return;
        }

        $customer = $this->findOrCreateCustomer($customerName);
        $transactionCacheKey = 'outbound:' . $invoice;

        if (! isset($context['transaction_id_cache'][$transactionCacheKey])) {
            $transaction = OutboundTransaction::create($this->filterColumns('outbound_transactions', [
                'transaction_number' => $this->nextOutboundImportTransactionNumber($date),
                'transaction_date' => $date->toDateString(),
                'outbound_type' => 'penjualan',
                'reference_number' => $invoice,
                'invoice_number' => $invoice,
                'customer_id' => $customer?->id,
                'warehouse_id' => $warehouseId,
                'sales_name' => $salesName ?: 'Admin',
                'driver_name' => null,
                'due_date' => null,
                'note' => 'Import barang keluar. Invoice: ' . $invoice,
                'status' => $this->statusAfterImport(),
                'sub_total' => 0,
                'discount_amount' => 0,
                'vat_percent' => 0,
                'vat_amount' => 0,
                'other_cost' => 0,
                'grand_total' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'submitted_by' => $this->userId(),
                'submitted_at' => now(),
                'approved_by' => $this->statusAfterImport() === 'approved' ? $this->userId() : null,
                'approved_at' => $this->statusAfterImport() === 'approved' ? now() : null,
                'approval_note' => $this->statusAfterImport() === 'approved'
                    ? 'Auto approved dari proses import.'
                    : null,
                'source' => $source,
            ]));

            $context['transaction_id_cache'][$transactionCacheKey] = $transaction->id;
            $context['created_transactions']++;
        }

        $transactionId = $context['transaction_id_cache'][$transactionCacheKey];

        $item = OutboundTransactionItem::create($this->filterColumns('outbound_transaction_items', [
            'outbound_transaction_id' => $transactionId,
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'unit_id' => $this->getProductValue($product, ['unit_id']),
            'qty' => $qty,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'price' => $unitPrice,
            'discount_amount' => 0,
            'subtotal' => $subtotal,
            'sub_total' => $subtotal,
            'stock_before_submit' => 0,
            'stock_after_submit' => 0,
            'product_code_snapshot' => $this->getProductValue($product, ['product_code', 'code', 'sku']),
            'product_name_snapshot' => $this->getProductValue($product, ['name', 'product_name']) ?: $product->getAttribute('name'),
            'unit_name_snapshot' => $this->getProductUnitName($product),
            'note' => $note,
        ]));

        $this->incrementTransactionTotals('outbound_transactions', $transactionId, $subtotal);

        if ($this->shouldUpdateOperationalStock()) {
            $stockResult = $this->applyStockMovement(
                productId: (int) $product->id,
                warehouseId: $warehouseId,
                qty: $qty,
                movementType: 'out',
                referenceType: 'outbound_transaction',
                referenceId: $transactionId,
                description: 'Import barang keluar. Invoice: ' . $invoice,
                movementDate: $date,
            );

            DB::table('outbound_transaction_items')
                ->where('id', $item->id)
                ->update($this->filterColumns('outbound_transaction_items', [
                    'stock_before_submit' => $stockResult['stock_before'],
                    'stock_after_submit' => $stockResult['stock_after'],
                    'updated_at' => now(),
                ]));
        }

        $context['imported_rows']++;
    }

    private function incrementTransactionTotals(string $table, int $transactionId, float $subtotal): void
    {
        foreach (['sub_total', 'grand_total', 'remaining_amount'] as $column) {
            if (DBSchema::hasColumn($table, $column)) {
                DB::table($table)->where('id', $transactionId)->increment($column, $subtotal);
            }
        }
    }

    private function shouldUpdateOperationalStock(): bool
    {
        $updateStock = $this->state['update_stock_operational']
            ?? $this->state['update_operational_stock']
            ?? $this->state['update_stock']
            ?? $this->state['is_update_stock']
            ?? $this->state['sync_stock']
            ?? true;

        $status = strtolower($this->statusAfterImport());

        $isApproved = in_array($status, [
            'approved',
            'approve',
            'disetujui',
            'selesai',
            'completed',
        ], true);

        return $isApproved && filter_var($updateStock, \FILTER_VALIDATE_BOOL);
    }

    private function applyStockMovement(
        int $productId,
        int $warehouseId,
        float $qty,
        string $movementType,
        string $referenceType,
        int $referenceId,
        string $description,
        Carbon $movementDate,
    ): array {
        if (! DBSchema::hasTable('stock_balances') || ! DBSchema::hasTable('stock_movements')) {
            return [
                'stock_before' => 0,
                'stock_after' => 0,
            ];
        }

        $stockBalance = DB::table('stock_balances')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        $stockBefore = (float) ($stockBalance->qty_on_hand ?? 0);

        $stockAfter = $movementType === 'in'
            ? $stockBefore + $qty
            : $stockBefore - $qty;

        if ($stockBalance) {
            DB::table('stock_balances')
                ->where('id', $stockBalance->id)
                ->update([
                    'qty_on_hand' => $stockAfter,
                    'qty_reserved' => $stockBalance->qty_reserved ?? 0,
                    'minimum_stock' => $stockBalance->minimum_stock ?? $this->productMinimumStock($productId),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('stock_balances')->insert($this->filterColumns('stock_balances', [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'qty_on_hand' => $stockAfter,
                'qty_reserved' => 0,
                'minimum_stock' => $this->productMinimumStock($productId),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        DB::table('stock_movements')->insert($this->filterColumns('stock_movements', [
            'movement_number' => $this->nextStockMovementNumber($movementType, $movementDate),
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'qty_in' => $movementType === 'in' ? $qty : 0,
            'qty_out' => $movementType === 'out' ? $qty : 0,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'description' => $description,
            'created_by' => $this->userId(),
            'created_at' => $movementDate->copy()->setTimeFrom(now()),
            'updated_at' => now(),
        ]));

        return [
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
        ];
    }

    private function productMinimumStock(int $productId): float
    {
        if (! DBSchema::hasTable('products')) {
            return 0;
        }

        if (DBSchema::hasColumn('products', 'minimum_stock')) {
            return (float) (DB::table('products')->where('id', $productId)->value('minimum_stock') ?? 0);
        }

        if (DBSchema::hasColumn('products', 'min_stock')) {
            return (float) (DB::table('products')->where('id', $productId)->value('min_stock') ?? 0);
        }

        return 0;
    }

    private function nextStockMovementNumber(string $movementType, Carbon $date): string
    {
        $prefix = $movementType === 'in' ? 'MOV-IN' : 'MOV-OUT';
        $dateText = $date->format('Ymd');

        $lastNumber = DB::table('stock_movements')
            ->where('movement_number', 'like', "{$prefix}-{$dateText}-%")
            ->orderByDesc('movement_number')
            ->value('movement_number');

        $nextSequence = 1;

        if ($lastNumber) {
            $nextSequence = ((int) substr((string) $lastNumber, -6)) + 1;
        }

        return "{$prefix}-{$dateText}-" . str_pad((string) $nextSequence, 6, '0', \STR_PAD_LEFT);
    }

    private function filterColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, string $column): bool => DBSchema::hasColumn($table, $column))
            ->all();
    }

    private function newImportContext(): array
    {
        return [
            'total_rows' => 0,
            'imported_rows' => 0,
            'skipped_rows' => 0,
            'created_transactions' => 0,
            'skipped_messages' => [],
            'transaction_id_cache' => [],
            'duplicate_invoice_cache' => [],
        ];
    }

    private function skipRow(array &$context, string $message): void
    {
        $context['skipped_rows']++;

        if (count($context['skipped_messages']) < 80) {
            $context['skipped_messages'][] = $message;
        }
    }

    private function updateProgressLog(?InventoryImportLog $log, array $context): void
    {
        $log?->update([
            'total_rows' => $context['total_rows'],
            'imported_rows' => $context['imported_rows'],
            'skipped_rows' => $context['skipped_rows'],
            'message' => "Import sedang berjalan. Baris terbaca: {$context['total_rows']}. Berhasil: {$context['imported_rows']}. Dilewati: {$context['skipped_rows']}.",
        ]);
    }

    private function finishContext(array $context, string $messagePrefix): array
    {
        return [
            'total_rows' => $context['total_rows'],
            'imported_rows' => $context['imported_rows'],
            'skipped_rows' => $context['skipped_rows'],
            'message' => $messagePrefix . " Transaksi dibuat: {$context['created_transactions']}. Item berhasil: {$context['imported_rows']}. Dilewati: {$context['skipped_rows']}.",
            'error_message' => empty($context['skipped_messages'])
                ? null
                : implode("\n", $context['skipped_messages']),
        ];
    }

    private function normalizeRecord(array $record): array
    {
        $normalized = [];

        foreach ($record as $key => $value) {
            $normalized[$this->normalizeKey((string) $key)] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    private function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = str_replace(['.', '/', '-', ' '], '_', $key);
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
        $key = preg_replace('/_+/', '_', $key);

        return trim((string) $key, '_');
    }

    private function recordValue(array $record, array $keys): mixed
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);

            if (array_key_exists($normalizedKey, $record) && $record[$normalizedKey] !== null && $record[$normalizedKey] !== '') {
                return $record[$normalizedKey];
            }
        }

        return null;
    }

    private function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' || $value === '-' ? null : $value;
    }

    private function cell(Collection $values, int $index): ?string
    {
        $value = $values->get($index);

        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function isEmptyExcelRow(Collection $values): bool
    {
        return $values
            ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
            ->isEmpty();
    }

    private function isOutboundHeaderRow(Collection $values): bool
    {
        $joined = strtoupper($values->implode(' '));

        return str_contains($joined, 'NO. INVOICE')
            || str_contains($joined, 'NAMA CUSTOMER')
            || str_contains($joined, 'NAMA BARANG')
            || str_contains($joined, 'LAPORAN PENJUALAN')
            || str_contains($joined, 'MONITORING PO')
            || str_contains($joined, 'PERIODE');
    }

    private function isOutboundTotalRow(Collection $values): bool
    {
        $productName = strtoupper((string) ($this->cell($values, 4) ?? ''));

        return $productName === 'TOTAL';
    }

    private function isOutboundGrandTotalRow(Collection $values): bool
    {
        $firstColumn = strtoupper((string) ($this->cell($values, 0) ?? ''));

        return str_contains($firstColumn, 'GRAND TOTAL');
    }

    private function toDate(mixed $value): ?Carbon
{
    try {
        if ($value instanceof \DateTimeInterface) {
            return $this->normalizeImportedDateYear(Carbon::instance($value));
        }

        if (is_numeric($value)) {
            return $this->normalizeImportedDateYear(
                Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))
            );
        }

        if (is_string($value) && trim($value) !== '') {
            $value = trim($value);

            $formats = [
                'd/m/Y',
                'd-m-Y',
                'd.m.Y',
                'Y-m-d',
                'Y/m/d',
                'm/d/Y',

                // Tambahan untuk format tahun 2 digit dari Excel
                'd/m/y',
                'd-m-y',
                'd.m.y',
                'd-M-y',
                'd M y',
                'd-M-Y',
                'd M Y',
            ];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $value);

                    if ($date instanceof Carbon) {
                        return $this->normalizeImportedDateYear($date);
                    }
                } catch (Throwable) {
                    continue;
                }
            }

            return $this->normalizeImportedDateYear(Carbon::parse($value));
        }
    } catch (Throwable) {
        return null;
    }

    return null;
}

private function importYear(): int
{
    $year = (int) (
        $this->state['import_year']
        ?? $this->state['year']
        ?? $this->state['tahun']
        ?? env('INVENTORY_IMPORT_YEAR', 2025)
    );

    return $year >= 2000 ? $year : 2025;
}

private function normalizeImportedDateYear(Carbon $date): ?Carbon
{
    if ($date->year >= 2000 && $date->year <= ((int) now()->format('Y')) + 1) {
        return $date;
    }

    if ($date->year < 2000) {
        try {
            return $date->copy()->setYear($this->importYear());
        } catch (Throwable) {
            return null;
        }
    }

    return null;
}

    private function toNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return 0;
        }

        $text = trim((string) $value);

        if ($text === '' || $text === '-' || str_starts_with($text, '#')) {
            return 0;
        }

        $isNegative = false;

        if (str_contains($text, '(') && str_contains($text, ')')) {
            $isNegative = true;
        }

        if (str_starts_with($text, '-')) {
            $isNegative = true;
        }

        $text = str_replace(
            ["\xc2\xa0", ' ', 'Rp', 'rp', 'IDR', 'idr'],
            '',
            $text
        );

        $text = preg_replace('/[^0-9,.\-]/', '', $text);

        if ($text === '' || $text === '-' || $text === null) {
            return 0;
        }

        $text = str_replace('-', '', $text);

        $hasComma = str_contains($text, ',');
        $hasDot = str_contains($text, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($text, ',');
            $lastDot = strrpos($text, '.');

            if ($lastDot > $lastComma) {
                $text = str_replace(',', '', $text);
            } else {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            }
        } elseif ($hasComma) {
            $commaCount = substr_count($text, ',');

            if ($commaCount > 1) {
                $text = str_replace(',', '', $text);
            } else {
                [$before, $after] = array_pad(explode(',', $text, 2), 2, '');

                if (strlen($after) === 3 && strlen($before) <= 3) {
                    $text = str_replace(',', '', $text);
                } else {
                    $text = str_replace(',', '.', $text);
                }
            }
        } elseif ($hasDot) {
            $dotCount = substr_count($text, '.');

            if ($dotCount > 1) {
                $lastDot = strrpos($text, '.');
                $after = substr($text, $lastDot + 1);

                if (strlen($after) === 3) {
                    $text = str_replace('.', '', $text);
                } else {
                    $integerPart = substr($text, 0, $lastDot);
                    $decimalPart = substr($text, $lastDot + 1);

                    $text = str_replace('.', '', $integerPart) . '.' . $decimalPart;
                }
            } else {
                [$before, $after] = array_pad(explode('.', $text, 2), 2, '');

                if (strlen($after) === 3 && strlen($before) <= 3) {
                    $text = str_replace('.', '', $text);
                }
            }
        }

        if (! is_numeric($text)) {
            return 0;
        }

        $number = (float) $text;

        return $isNegative ? -$number : $number;
    }

    private function isNonStockProductLine(string $productName): bool
    {
        $name = strtoupper(trim($productName));

        if ($name === '' || $name === 'TOTAL') {
            return true;
        }

        $nonStockKeywords = [
            'ONGKIR',
            'VACUM',
            'VACUUM',
            'KARUNG',
            'PACKING',
            'PAKING',
            'BIAYA',
            'JASA',
            'DISKON',
            'POTONGAN',
            'ADMIN',
            'FEE',
            'PPN',
            'PAJAK',
        ];

        foreach ($nonStockKeywords as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function findProductByExcelName(string $productName): ?Product
    {
        $productName = $this->cleanImportedProductName($productName);

        if ($productName === '') {
            return null;
        }

        $columns = collect(['name', 'product_name', 'full_name', 'product_code', 'code', 'sku'])
            ->filter(fn (string $column): bool => DBSchema::hasColumn('products', $column))
            ->values();

        if ($columns->isEmpty()) {
            return $this->createImportedProductFromName($productName);
        }

        $exact = Product::query()
            ->where(function ($query) use ($columns, $productName): void {
                foreach ($columns as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}($column, $productName);
                }
            })
            ->first();

        if ($exact) {
            return $exact;
        }

        $targetKeys = $this->productMatchKeys($productName);

        $bestProductId = null;
        $bestScore = 0;

        Product::query()
            ->select(array_values(array_unique(array_merge(['id'], $columns->all()))))
            ->chunkById(500, function ($products) use ($columns, $targetKeys, &$bestProductId, &$bestScore): void {
                foreach ($products as $product) {
                    $candidateKeys = [];

                    foreach ($columns as $column) {
                        $value = $product->{$column} ?? null;

                        if ($value !== null && trim((string) $value) !== '') {
                            $candidateKeys = array_merge(
                                $candidateKeys,
                                $this->productMatchKeys((string) $value)
                            );
                        }
                    }

                    $candidateKeys = array_values(array_unique(array_filter($candidateKeys)));

                    $score = $this->productMatchScore($targetKeys, $candidateKeys);

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestProductId = (int) $product->id;
                    }
                }
            });

        if ($bestProductId && $bestScore >= 92) {
            return Product::query()
                ->where('id', $bestProductId)
                ->first();
        }

        return $this->createImportedProductFromName($productName);
    }

    private function cleanImportedProductName(string $productName): string
    {
        $productName = trim(preg_replace('/\s+/', ' ', $productName));
        $productName = str_replace(['×', '*'], 'X', $productName);
        $productName = preg_replace('/\s*[xX]\s*/', 'X', $productName);

        return trim((string) $productName);
    }

    private function productMatchKeys(string $name): array
    {
        $name = strtoupper($this->cleanImportedProductName($name));

        $plain = preg_replace('/[^A-Z0-9]+/', '', $name);

        $withoutLeadingZeroNumbers = preg_replace_callback('/\d+/', function (array $matches): string {
            return (string) ((int) $matches[0]);
        }, $plain);

        $dimensionNormalized = preg_replace_callback(
            '/(\d+)\s*X\s*(\d+)\s*X\s*(\d+)/i',
            function (array $matches): string {
                return ((int) $matches[1]) . 'X' . ((int) $matches[2]) . 'X' . ((int) $matches[3]);
            },
            $name
        );

        $dimensionNormalized = preg_replace('/[^A-Z0-9]+/', '', strtoupper($dimensionNormalized));

        return array_values(array_unique(array_filter([
            $plain,
            $withoutLeadingZeroNumbers,
            $dimensionNormalized,
        ])));
    }

    private function productMatchScore(array $targetKeys, array $candidateKeys): int
    {
        $bestScore = 0;

        foreach ($targetKeys as $target) {
            foreach ($candidateKeys as $candidate) {
                if ($target === '' || $candidate === '') {
                    continue;
                }

                if ($target === $candidate) {
                    return 100;
                }

                if (str_contains($candidate, $target) || str_contains($target, $candidate)) {
                    $bestScore = max($bestScore, 94);
                    continue;
                }

                similar_text($target, $candidate, $percent);

                $bestScore = max($bestScore, (int) round($percent));
            }
        }

        return $bestScore;
    }

    private function createImportedProductFromName(string $productName): ?Product
    {
        if (! DBSchema::hasTable('products')) {
            return null;
        }

        $productName = $this->cleanImportedProductName($productName);
        $code = $this->nextImportedProductCode($productName);

        $data = [
            'code' => $code,
            'product_code' => $code,
            'sku' => $code,

            'name' => $productName,
            'product_name' => $productName,
            'full_name' => $productName,

            'description' => 'Auto create dari proses import Excel.',
            'is_active' => true,
            'status' => 'active',

            'default_purchase_price' => 0,
            'default_selling_price' => 0,
            'purchase_price' => 0,
            'selling_price' => 0,
            'minimum_stock' => 0,
            'min_stock' => 0,

            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->fillProductDefaultForeignKey($data, 'product_type_id', ['product_types']);
        $this->fillProductDefaultForeignKey($data, 'product_density_id', ['product_densities']);
        $this->fillProductDefaultForeignKey($data, 'product_category_id', ['product_categories', 'categories']);
        $this->fillProductDefaultForeignKey($data, 'unit_id', ['units']);

        try {
            $insertData = $this->filterColumns('products', $data);

            $productId = DB::table('products')->insertGetId($insertData);

            return Product::query()
                ->where('id', $productId)
                ->first();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function fillProductDefaultForeignKey(array &$data, string $column, array $tables): void
    {
        if (! DBSchema::hasColumn('products', $column)) {
            return;
        }

        foreach ($tables as $table) {
            if (! DBSchema::hasTable($table)) {
                continue;
            }

            $id = DB::table($table)->orderBy('id')->value('id');

            if ($id) {
                $data[$column] = $id;
                return;
            }

            $createdId = $this->createDefaultLookupRow($table);

            if ($createdId) {
                $data[$column] = $createdId;
                return;
            }
        }

        if ($this->isColumnNullable('products', $column)) {
            $data[$column] = null;
        }
    }

    private function createDefaultLookupRow(string $table): ?int
    {
        try {
            $code = 'IMP-' . strtoupper(substr(md5($table), 0, 8));
            $name = 'Import Default';

            $data = [
                'code' => $code,
                'kode' => $code,
                'name' => $name,
                'nama' => $name,
                'description' => 'Auto create default dari proses import Excel.',
                'is_active' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $insertData = $this->filterColumns($table, $data);

            if (empty($insertData)) {
                return null;
            }

            return (int) DB::table($table)->insertGetId($insertData);
        } catch (Throwable) {
            return null;
        }
    }

    private function nextImportedProductCode(string $productName): string
    {
        $baseCode = 'PRD-IMP-' . strtoupper(substr(md5($productName), 0, 8));
        $code = $baseCode;
        $sequence = 1;

        $codeColumns = collect(['code', 'product_code', 'sku'])
            ->filter(fn (string $column): bool => DBSchema::hasColumn('products', $column))
            ->values();

        while ($codeColumns->isNotEmpty()) {
            $exists = DB::table('products')
                ->where(function ($query) use ($codeColumns, $code): void {
                    foreach ($codeColumns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $query->{$method}($column, $code);
                    }
                })
                ->exists();

            if (! $exists) {
                return $code;
            }

            $sequence++;
            $code = $baseCode . '-' . $sequence;
        }

        return $code;
    }

    private function isColumnNullable(string $table, string $column): bool
    {
        try {
            $database = DB::getDatabaseName();

            $result = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->value('IS_NULLABLE');

            return strtoupper((string) $result) === 'YES';
        } catch (Throwable) {
            return true;
        }
    }

    private function findOrCreateCustomer(?string $customerName): ?Customer
    {
        if (! $customerName || ! DBSchema::hasTable('customers')) {
            return null;
        }

        $customerName = trim(preg_replace('/\s+/', ' ', $customerName));

        $nameColumn = collect(['customer_name', 'name', 'nama_customer', 'nama', 'company_name'])
            ->first(fn (string $column): bool => DBSchema::hasColumn('customers', $column));

        if (! $nameColumn) {
            return null;
        }

        $customer = Customer::query()->where($nameColumn, $customerName)->first();

        if ($customer) {
            return $customer;
        }

        $customerCode = 'CUST-IMP-' . strtoupper(substr(md5($customerName), 0, 8));

        return Customer::create($this->filterColumns('customers', [
            $nameColumn => $customerName,
            'customer_code' => $customerCode,
            'code' => $customerCode,
            'kode_customer' => $customerCode,
            'phone' => '-',
            'telephone' => '-',
            'telp' => '-',
            'address' => '-',
            'alamat' => '-',
            'email' => 'import-' . strtolower(substr(md5($customerName), 0, 12)) . '@example.local',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->userId(),
            'updated_by' => $this->userId(),
        ]));
    }

    private function findOrCreateSupplier(?string $supplierName): ?Supplier
    {
        if (! $supplierName || ! DBSchema::hasTable('suppliers')) {
            return null;
        }

        $supplierName = trim(preg_replace('/\s+/', ' ', $supplierName));

        $nameColumn = collect(['supplier_name', 'name', 'nama_supplier', 'nama', 'company_name'])
            ->first(fn (string $column): bool => DBSchema::hasColumn('suppliers', $column));

        if (! $nameColumn) {
            return null;
        }

        $supplier = Supplier::query()->where($nameColumn, $supplierName)->first();

        if ($supplier) {
            return $supplier;
        }

        $supplierCode = 'SUP-IMP-' . strtoupper(substr(md5($supplierName), 0, 8));

        return Supplier::create($this->filterColumns('suppliers', [
            $nameColumn => $supplierName,
            'supplier_code' => $supplierCode,
            'code' => $supplierCode,
            'kode_supplier' => $supplierCode,
            'phone' => '-',
            'telephone' => '-',
            'telp' => '-',
            'address' => '-',
            'alamat' => '-',
            'email' => 'supplier-' . strtolower(substr(md5($supplierName), 0, 12)) . '@example.local',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->userId(),
            'updated_by' => $this->userId(),
        ]));
    }

    private function getProductValue(Product $product, array $columns): mixed
    {
        foreach ($columns as $column) {
            if (DBSchema::hasColumn('products', $column) && $product->{$column} !== null) {
                return $product->{$column};
            }
        }

        return null;
    }

    private function getProductUnitName(Product $product): ?string
    {
        if (method_exists($product, 'unit') && $product->unit) {
            foreach (['name', 'unit_name', 'code'] as $column) {
                if (isset($product->unit->{$column})) {
                    return $product->unit->{$column};
                }
            }
        }

        return null;
    }

    private function nextInboundImportTransactionNumber(Carbon $date): string
    {
        $prefix = 'IN-IMP-' . $date->format('Ymd');

        $lastNumber = InboundTransaction::query()
            ->where('transaction_number', 'like', $prefix . '-%')
            ->pluck('transaction_number')
            ->map(function ($number) use ($prefix) {
                if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', (string) $number, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return $prefix . '-' . str_pad((string) ($lastNumber + 1), 4, '0', \STR_PAD_LEFT);
    }

    private function nextOutboundImportTransactionNumber(Carbon $date): string
    {
        $prefix = 'OUT-IMP-' . $date->format('Ymd');

        $lastNumber = OutboundTransaction::query()
            ->where('transaction_number', 'like', $prefix . '-%')
            ->pluck('transaction_number')
            ->map(function ($number) use ($prefix) {
                if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', (string) $number, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return $prefix . '-' . str_pad((string) ($lastNumber + 1), 4, '0', \STR_PAD_LEFT);
    }
}

class ExcelChunkReadFilter implements IReadFilter
{
    public function __construct(
        private int $startRow,
        private int $endRow
    ) {
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        return $row >= $this->startRow && $row <= $this->endRow;
    }
}