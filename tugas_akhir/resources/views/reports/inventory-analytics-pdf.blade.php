<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Analitik Inventori</title>
    <style>
        @page { margin: 30px 34px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #1e293b;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.45;
        }
        h1, h2, h3, p { margin: 0; }
        .header {
            padding-bottom: 14px;
            border-bottom: 3px solid #2563eb;
        }
        .eyebrow {
            color: #2563eb;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        h1 {
            margin-top: 5px;
            color: #0f172a;
            font-size: 22px;
        }
        .subtitle {
            margin-top: 3px;
            color: #64748b;
        }
        .meta,
        .kpi,
        .summary-table,
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta { margin-top: 12px; }
        .meta td {
            width: 50%;
            padding: 4px 6px;
            border: 1px solid #dbeafe;
            vertical-align: top;
        }
        .label {
            display: block;
            color: #64748b;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .value {
            display: block;
            margin-top: 2px;
            color: #0f172a;
            font-weight: bold;
        }
        .section {
            margin-top: 17px;
        }
        .section-title {
            padding: 6px 9px;
            border-left: 4px solid #2563eb;
            background: #eff6ff;
            color: #0f172a;
            font-size: 12px;
        }
        .section-note {
            margin-top: 5px;
            color: #64748b;
            font-size: 8px;
        }
        .kpi {
            margin-top: 8px;
            table-layout: fixed;
        }
        .kpi td {
            padding: 9px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .kpi-value {
            display: block;
            margin-top: 4px;
            color: #0f172a;
            font-size: 16px;
            font-weight: bold;
        }
        .positive { color: #15803d; }
        .negative { color: #dc2626; }
        .insight {
            margin-top: 7px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            page-break-inside: avoid;
        }
        .insight-title {
            color: #0f172a;
            font-weight: bold;
        }
        .insight-body {
            margin-top: 3px;
            color: #475569;
        }
        .data-table { margin-top: 8px; }
        .data-table th {
            padding: 6px;
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
            color: #334155;
            font-size: 7px;
            text-align: left;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 6px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .number { text-align: right; }
        .status {
            font-size: 7px;
            font-weight: bold;
        }
        .empty {
            margin-top: 8px;
            padding: 10px;
            border: 1px dashed #cbd5e1;
            color: #64748b;
            text-align: center;
        }
        .footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 7px;
        }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    @php
        $summary = $analytics['summary'];
        $classification = $analytics['classification'];
    @endphp

    <div class="header">
        <div class="eyebrow">PT Naura Sukses Abadi · Business Intelligence</div>
        <h1>Laporan Analitik Inventori</h1>
        <p class="subtitle">Ringkasan historis berbasis tabel fakta dan dimensi Data Warehouse.</p>

        <table class="meta">
            <tr>
                <td><span class="label">Periode</span><span class="value">{{ $periodLabel }}</span></td>
                <td><span class="label">Gudang</span><span class="value">{{ $warehouseLabel }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Produk</span><span class="value">{{ $productLabel }}</span></td>
                <td><span class="label">Kategori</span><span class="value">{{ $categoryLabel }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Sinkronisasi ETL Terakhir</span><span class="value">{{ $lastSyncAt?->format('d M Y H:i:s') ?? '-' }}</span></td>
                <td><span class="label">Laporan Dibuat</span><span class="value">{{ $generatedAt->format('d M Y H:i:s') }} WIB</span></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Ringkasan Pergerakan Inventori</h2>
        <table class="kpi">
            <tr>
                <td><span class="label">Qty Masuk</span><span class="kpi-value positive">{{ number_format($summary['qty_in'], 0, ',', '.') }}</span></td>
                <td><span class="label">Qty Keluar</span><span class="kpi-value negative">{{ number_format($summary['qty_out'], 0, ',', '.') }}</span></td>
                <td><span class="label">Pergerakan Bersih</span><span class="kpi-value {{ $summary['net_movement'] >= 0 ? 'positive' : 'negative' }}">{{ $summary['net_movement'] > 0 ? '+' : '' }}{{ number_format($summary['net_movement'], 0, ',', '.') }}</span></td>
            </tr>
        </table>

        <table class="kpi">
            <tr>
                <td><span class="label">Perubahan Qty Masuk</span><span class="kpi-value">{{ $summary['qty_in_change'] === null ? 'Tidak tersedia' : number_format($summary['qty_in_change'], 2, ',', '.') . '%' }}</span></td>
                <td><span class="label">Perubahan Qty Keluar</span><span class="kpi-value">{{ $summary['qty_out_change'] === null ? 'Tidak tersedia' : number_format($summary['qty_out_change'], 2, ',', '.') . '%' }}</span></td>
                <td><span class="label">Klasifikasi Produk</span><span class="kpi-value">{{ $classification['counts']['fast'] }} / {{ $classification['counts']['slow'] }} / {{ $classification['counts']['non_moving'] }}</span></td>
            </tr>
        </table>
        <p class="section-note">{{ $analytics['comparison_note'] }}</p>
    </div>

    <div class="section">
        <h2 class="section-title">Insight dan Dasar Pengambilan Keputusan</h2>
        @foreach ($analytics['insights'] as $insight)
            <div class="insight">
                <div class="insight-title">{{ $insight['title'] }}</div>
                <div class="insight-body">{{ $insight['body'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="section">
        <h2 class="section-title">Perbandingan Gudang</h2>
        @if ($analytics['warehouses'] === [])
            <div class="empty">Belum ada data gudang yang dapat dibandingkan.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Gudang</th>
                        <th class="number">Qty Masuk</th>
                        <th class="number">Qty Keluar</th>
                        <th class="number">Pergerakan Bersih</th>
                        <th class="number">Perlu Diperiksa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($analytics['warehouses'] as $warehouse)
                        <tr>
                            <td>{{ $warehouse['name'] }}</td>
                            <td class="number">{{ number_format($warehouse['qty_in'], 0, ',', '.') }}</td>
                            <td class="number">{{ number_format($warehouse['qty_out'], 0, ',', '.') }}</td>
                            <td class="number">{{ $warehouse['net_movement'] > 0 ? '+' : '' }}{{ number_format($warehouse['net_movement'], 0, ',', '.') }}</td>
                            <td class="number">{{ number_format($warehouse['attention_count'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h2 class="section-title">Klasifikasi Pergerakan Produk</h2>
        <p class="section-note">{{ $classification['rule'] }}</p>
        @if ($classification['rows'] === [])
            <div class="empty">Belum ada produk yang dapat diklasifikasikan.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>Klasifikasi</th>
                        <th class="number">Qty Keluar</th>
                        <th class="number">Frekuensi</th>
                        <th>Terakhir Bergerak</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($classification['rows'] as $product)
                        <tr>
                            <td>{{ $product['name'] }}</td>
                            <td>{{ $product['category'] }}</td>
                            <td><span class="status">{{ $product['classification_label'] }}</span></td>
                            <td class="number">{{ number_format($product['total_qty_out'], 0, ',', '.') }}</td>
                            <td class="number">{{ number_format($product['movement_frequency'], 0, ',', '.') }}</td>
                            <td>{{ $product['last_movement_date'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h2 class="section-title">Peringatan Stok</h2>
        @if ($analytics['stock_alerts'] === [])
            <div class="empty">Tidak ada stok menipis atau habis pada snapshot terakhir dalam filter terpilih.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Gudang</th>
                        <th class="number">Tersedia</th>
                        <th class="number">Minimum</th>
                        <th class="number">Kekurangan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($analytics['stock_alerts'] as $alert)
                        <tr>
                            <td>{{ $alert['product_name'] }}</td>
                            <td>{{ $alert['warehouse_name'] }}</td>
                            <td class="number">{{ number_format($alert['qty_available'], 0, ',', '.') }}</td>
                            <td class="number">{{ number_format($alert['minimum_stock'], 0, ',', '.') }}</td>
                            <td class="number">{{ number_format($alert['shortage'], 0, ',', '.') }}</td>
                            <td>{{ $alert['status_label'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        Sumber: Data Warehouse inventori hasil proses ETL. Laporan ini merupakan keluaran analitik pendukung keputusan dan tidak menjalankan keputusan operasional secara otomatis.
    </div>
</body>
</html>