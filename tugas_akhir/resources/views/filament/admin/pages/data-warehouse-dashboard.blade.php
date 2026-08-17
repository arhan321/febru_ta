<x-filament-panels::page>
    <style>
        .dw-page {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 28px;
            padding: 14px 12px 44px;
            overflow: visible;
        }

        .dw-hero {
            position: relative;
            overflow: visible;
            border-radius: 24px;
            border: 1px solid rgba(148, 163, 184, .28);
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, .18), transparent 36%),
                radial-gradient(circle at bottom right, rgba(16, 185, 129, .16), transparent 34%),
                linear-gradient(135deg, #ffffff, #f8fafc);
            padding: 24px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
        }

        .dw-hero-inner {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 210px;
            gap: 24px;
            align-items: center;
        }

        .dw-badge,
        .dw-section-kicker {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .02em;
        }

        .dw-title {
            margin-top: 14px;
            font-size: 28px;
            line-height: 1.2;
            font-weight: 900;
            color: #0f172a;
        }

        .dw-description {
            margin-top: 10px;
            max-width: 760px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.65;
        }

        .dw-meta {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .dw-meta-item,
        .dw-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .82);
            border: 1px solid rgba(226, 232, 240, .9);
            padding: 9px 12px;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
            white-space: nowrap;
        }

        .dw-chip {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #dbeafe;
            box-shadow: none;
        }

        .dw-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .dw-btn {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 14px;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
        }

        .dw-btn:hover {
            transform: translateY(-1px);
        }

        .dw-btn:disabled {
            opacity: .65;
            cursor: wait;
        }

        .dw-btn-primary {
            color: #ffffff;
            border: 0;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 12px 24px rgba(37, 99, 235, .26);
        }

        .dw-btn-secondary {
            color: #334155;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
        }

        .dw-btn-report {
    color: #ffffff;
    background: linear-gradient(135deg, #0d9488, #059669);
    border: 0;
    box-shadow: 0 8px 20px rgba(13, 148, 136, .22);
    text-decoration: none;
}

        .dw-section-block {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 2px 0;
            overflow: visible;
        }

        .dw-section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            padding-left: 2px;
            padding-right: 2px;
        }

        .dw-section-heading-main {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .dw-section-title {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
        }

        .dw-section-subtitle,
        .dw-section-caption {
            margin: 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .dw-filter-card {
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, .95);
            background: #ffffff;
            padding: 22px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .06);
            overflow: visible;
        }

        .dw-filter-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .dw-active-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .dw-filter-grid-calendar {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            align-items: stretch;
        }

        .dw-chart-filter-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 16px;
            align-items: stretch;
        }

        .dw-source-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            align-items: stretch;
        }

        .dw-field {
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 14px;
        }

        .dw-label {
            display: block;
            margin-bottom: 10px;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
        }

        .dw-select,
        .dw-input {
            width: 100%;
            height: 46px;
            border-radius: 14px;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        }

        .dw-select {
            appearance: none;
            padding: 0 42px 0 14px;
            background-image:
                linear-gradient(45deg, transparent 50%, #64748b 50%),
                linear-gradient(135deg, #64748b 50%, transparent 50%);
            background-position:
                calc(100% - 20px) 19px,
                calc(100% - 15px) 19px;
            background-size: 5px 5px, 5px 5px;
            background-repeat: no-repeat;
        }

        .dw-input {
            padding: 0 14px;
        }

        .dw-select:focus,
        .dw-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
        }

        .dw-filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .dw-small-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 12px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 9px 13px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: all .18s ease;
        }

        .dw-small-btn:hover {
            background: #dbeafe;
            transform: translateY(-1px);
        }

        .dw-widget-wrap {
            border-radius: 24px;
            overflow: visible;
            padding: 4px;
        }

        .dw-widget-wrap > * {
            overflow: visible !important;
        }

        .dw-chart-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            align-items: start;
        }

        .dw-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .dw-kpi-card,
        .dw-insight-card,
        .dw-table-card {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .05);
        }

        .dw-kpi-card {
            border-radius: 20px;
            padding: 18px;
        }

        .dw-kpi-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .dw-kpi-value {
            margin-top: 8px;
            color: #0f172a;
            font-size: 25px;
            line-height: 1.15;
            font-weight: 900;
        }

        .dw-kpi-note {
            margin-top: 8px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .dw-positive {
            color: #15803d;
        }

        .dw-negative {
            color: #dc2626;
        }

        .dw-neutral {
            color: #475569;
        }

        .dw-info {
            color: #2563eb;
        }

        .dw-insight-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .dw-insight-card {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            gap: 14px;
            align-items: start;
            border-radius: 20px;
            padding: 18px;
        }

        .dw-insight-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 18px;
            font-weight: 900;
        }

        .dw-insight-card[data-tone="positive"] .dw-insight-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .dw-insight-card[data-tone="warning"] .dw-insight-icon {
            background: #ffedd5;
            color: #c2410c;
        }

        .dw-insight-card[data-tone="danger"] .dw-insight-icon {
            background: #fee2e2;
            color: #dc2626;
        }

        .dw-insight-title {
            margin: 0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .dw-insight-body {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .dw-table-card {
            border-radius: 22px;
            overflow: hidden;
        }

        .dw-table-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .dw-table-title {
            margin: 0;
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
        }

        .dw-table-description {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .dw-table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .dw-table {
            width: 100%;
            min-width: 820px;
            border-collapse: collapse;
        }

        .dw-table th {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .03em;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dw-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .dw-table tr:last-child td {
            border-bottom: 0;
        }

        .dw-table .dw-number {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .dw-name {
            color: #0f172a;
            font-weight: 800;
        }

        .dw-muted {
            color: #94a3b8;
            font-size: 12px;
        }

        .dw-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .dw-status-fast,
        .dw-status-aman {
            background: #dcfce7;
            color: #15803d;
        }

        .dw-status-slow,
        .dw-status-menipis {
            background: #ffedd5;
            color: #c2410c;
        }

        .dw-status-non_moving,
        .dw-status-habis {
            background: #fee2e2;
            color: #dc2626;
        }

        .dw-focus-btn {
            border: 1px solid #dbeafe;
            border-radius: 9px;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 6px 9px;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
            white-space: nowrap;
        }

        .dw-empty-state {
            padding: 28px 20px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
            text-align: center;
        }

        .dw-source-list {
            margin: 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.7;
        }

        .dark .dw-hero {
            border-color: rgba(51, 65, 85, .8);
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, .24), transparent 36%),
                radial-gradient(circle at bottom right, rgba(16, 185, 129, .20), transparent 34%),
                linear-gradient(135deg, #0f172a, #111827);
        }

        .dark .dw-title,
        .dark .dw-section-title {
            color: #f8fafc;
        }

        .dark .dw-description,
        .dark .dw-section-subtitle,
        .dark .dw-section-caption,
        .dark .dw-source-list {
            color: #94a3b8;
        }

        .dark .dw-meta-item,
        .dark .dw-filter-card,
        .dark .dw-kpi-card,
        .dark .dw-insight-card,
        .dark .dw-table-card {
            background: #111827;
            border-color: #334155;
            color: #e5e7eb;
        }

        .dark .dw-kpi-value,
        .dark .dw-insight-title,
        .dark .dw-table-title,
        .dark .dw-name {
            color: #f8fafc;
        }

        .dark .dw-kpi-label,
        .dark .dw-kpi-note,
        .dark .dw-insight-body,
        .dark .dw-table-description,
        .dark .dw-empty-state {
            color: #94a3b8;
        }

        .dark .dw-table-header,
        .dark .dw-table th {
            background: #0f172a;
            border-color: #334155;
        }

        .dark .dw-table td {
            border-color: #1e293b;
            color: #cbd5e1;
        }

        .dark .dw-focus-btn {
            background: rgba(37, 99, 235, .18);
            border-color: #1d4ed8;
            color: #bfdbfe;
        }

        .dark .dw-btn-secondary {
            background: #0f172a;
            border-color: #334155;
            color: #e5e7eb;
        }

        .dark .dw-field {
            background: #0f172a;
            border-color: #334155;
        }

        .dark .dw-label {
            color: #e5e7eb;
        }

        .dark .dw-chip,
        .dark .dw-section-kicker,
        .dark .dw-badge {
            background: rgba(37, 99, 235, .18);
            color: #bfdbfe;
            border-color: #1d4ed8;
        }

        .dark .dw-select,
        .dark .dw-input {
            background-color: #111827;
            border-color: #334155;
            color: #f8fafc;
        }

        .dark .dw-small-btn {
            background: rgba(37, 99, 235, .18);
            border-color: #1d4ed8;
            color: #bfdbfe;
        }

        .dark .dw-small-btn:hover {
            background: rgba(37, 99, 235, .28);
        }

        @media (max-width: 1200px) {
            .dw-filter-grid-calendar,
            .dw-source-grid,
            .dw-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .dw-page {
                max-width: 100%;
            }

            .dw-hero-inner {
                grid-template-columns: 1fr;
            }

            .dw-actions {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .dw-btn {
                flex: 1;
                min-width: 180px;
            }

            .dw-chart-filter-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dw-page {
                padding-left: 4px;
                padding-right: 4px;
            }

            .dw-hero {
                padding: 20px;
                border-radius: 20px;
            }

            .dw-title {
                font-size: 24px;
            }

            .dw-filter-header,
            .dw-section-heading {
                flex-direction: column;
                align-items: flex-start;
            }

            .dw-active-filter {
                justify-content: flex-start;
            }

            .dw-filter-grid-calendar,
            .dw-source-grid,
            .dw-kpi-grid,
            .dw-insight-grid {
                grid-template-columns: 1fr;
            }

            .dw-actions {
                flex-direction: column;
            }

            .dw-btn {
                min-width: 0;
            }

            .dw-filter-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="dw-page">
        @php
            $advancedAnalytics = $this->getAdvancedAnalytics();
        @endphp

        <section class="dw-hero">
            <div class="dw-hero-inner">
                <div>
                    <span class="dw-badge">
                        📊 Data Warehouse Analytics
                    </span>

                    <h2 class="dw-title">
                        Dashboard Analitik Data Warehouse
                    </h2>

                    <p class="dw-description">
                        Pantau pergerakan inventori dari tabel <strong>dw_*</strong>. Data di halaman ini berasal dari proses ETL,
                        bukan langsung dari tabel operasional, sehingga dapat digunakan untuk analisis historis inventori.
                    </p>

                    <div class="dw-meta">
                        <span class="dw-meta-item">
                            ⚙️ Auto sync setiap 5 menit
                        </span>

                        <span class="dw-meta-item">
                            🧭 Periode: {{ $this->getPeriodLabel() }}
                        </span>

                        <span class="dw-meta-item">
                            🏭 Gudang: {{ $this->getWarehouseLabel() }}
                        </span>

                        <span class="dw-meta-item">
                            📦 Produk: {{ $this->getProductLabel() }}
                        </span>
                    </div>
                </div>

                <div class="dw-actions">
                    <button
                        type="button"
                        wire:click="syncNow"
                        wire:loading.attr="disabled"
                        wire:target="syncNow"
                        class="dw-btn dw-btn-primary"
                    >
                        <span wire:loading.remove wire:target="syncNow">
                            🔄 Sync DW Sekarang
                        </span>

                        <span wire:loading wire:target="syncNow">
                            Memproses Sync...
                        </span>
                    </button>

                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="dw-btn dw-btn-secondary"
                    >
                        ↩ Reset Filter
                    </button>

                    <a
    href="{{ route('reports.inventory-analytics.pdf', [
        'period' => $this->period,
        'startDate' => $this->startDate,
        'endDate' => $this->endDate,
        'warehouseId' => $this->warehouseId,
        'productId' => $this->productId,
        'productCategory' => $this->productCategory,
    ]) }}"
    class="dw-btn dw-btn-report"
>
    ↓ Download PDF
</a>
                </div>
            </div>
        </section>

        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        ETL MONITORING
                    </span>

                    <h3 class="dw-section-title">
                        Informasi Sinkronisasi ETL
                    </h3>

                    <p class="dw-section-caption">
                        Menampilkan waktu sinkronisasi terakhir, status ETL, tanggal data terbaru, dan jumlah baris fact movement
                        yang telah terbentuk pada data warehouse.
                    </p>
                </div>
            </div>

            <div class="dw-widget-wrap">
                @livewire(
                    \App\Filament\Admin\DataWarehouseWidgets\DataWarehouseEtlInfoWidget::class,
                    [],
                    key('dw-etl-info')
                )
            </div>
        </section>

        <section class="dw-section-block">
    <div class="dw-section-heading">
        <div class="dw-section-heading-main">
            <span class="dw-section-kicker">
                ETL HISTORY
            </span>

            <h3 class="dw-section-title">
                Riwayat Proses ETL
            </h3>

            <p class="dw-section-caption">
                Menampilkan riwayat sinkronisasi Data Warehouse inventori,
                termasuk kode batch, pemicu proses, pengguna, status,
                jumlah baris sumber dan target, durasi, serta waktu proses.
            </p>
        </div>
    </div>

    <div class="dw-widget-wrap">
        @livewire(
            \App\Filament\Admin\DataWarehouseWidgets\DataWarehouseEtlHistoryWidget::class,
            [],
            key('dw-etl-history')
        )
    </div>
</section>

        <section class="dw-filter-card">
            <div class="dw-filter-header">
                <div>
                    <h3 class="dw-section-title">
                        Filter Analitik
                    </h3>

                    <p class="dw-section-subtitle">
                        Gunakan dimensi waktu, gudang, produk, dan kategori produk untuk melihat data historis dari berbagai sudut pandang.
                    </p>
                </div>

                <div class="dw-active-filter">
                    <span class="dw-chip">
                        {{ $this->getPeriodLabel() }}
                    </span>

                    <span class="dw-chip">
                        {{ $this->getWarehouseLabel() }}
                    </span>

                    <span class="dw-chip">
                        {{ $this->getProductLabel() }}
                    </span>

                    <span class="dw-chip">
                        {{ $this->getProductCategoryLabel() }}
                    </span>
                </div>
            </div>

            <div class="dw-filter-grid-calendar">
                <div class="dw-field">
                    <label class="dw-label">
                        Periode Cepat
                    </label>

                    <select wire:model.change="period" class="dw-select">
                        @foreach ($this->getPeriodOptions() as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="dw-field">
                    <label class="dw-label">
                        Tanggal Mulai
                    </label>

                    <input
                        type="date"
                        wire:model.change="startDate"
                        class="dw-input"
                    >
                </div>

                <div class="dw-field">
                    <label class="dw-label">
                        Tanggal Selesai
                    </label>

                    <input
                        type="date"
                        wire:model.change="endDate"
                        class="dw-input"
                    >
                </div>

                <div class="dw-field">
                    <label class="dw-label">
                        Gudang
                    </label>

                    <select wire:model.change="warehouseId" class="dw-select">
                        <option value="">
                            Semua Gudang
                        </option>

                        @foreach ($this->getWarehouses() as $id => $name)
                            <option value="{{ $id }}">
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="dw-field">
                    <label class="dw-label">
                        Produk
                    </label>

                    <select wire:model.change="productId" class="dw-select">
                        <option value="">
                            Semua Produk
                        </option>

                        @foreach ($this->getProducts() as $id => $name)
                            <option value="{{ $id }}">
                                {{ \Illuminate\Support\Str::limit($name, 70) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="dw-field">
                    <label class="dw-label">
                        Kategori Produk
                    </label>

                    <select wire:model.change="productCategory" class="dw-select">
                        <option value="">
                            Semua Kategori
                        </option>

                        @foreach ($this->getProductCategories() as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="dw-filter-actions">
                <button
                    type="button"
                    wire:click="clearCustomDate"
                    class="dw-small-btn"
                >
                    🗓️ Kosongkan Tanggal
                </button>
            </div>
        </section>

        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        RINGKASAN DATA WAREHOUSE
                    </span>

                    <h3 class="dw-section-title">
                        Ringkasan Kinerja Inventori
                    </h3>

                    <p class="dw-section-caption">
                        Ringkasan jumlah produk, gudang, transaksi, kuantitas, dan kondisi stok
                        berdasarkan hasil sinkronisasi data warehouse.
                    </p>
                </div>
            </div>

            <div class="dw-widget-wrap">
                @livewire(
                    \App\Filament\Admin\DataWarehouseWidgets\DataWarehouseOverviewWidget::class,
                    [
                        'period' => $period,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'warehouseId' => $warehouseId,
                    ],
                    key('dw-overview-' . $period . '-' . $startDate . '-' . $endDate . '-' . $warehouseId)
                )
            </div>
        </section>

        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        ANALISIS PENDUKUNG KEPUTUSAN
                    </span>

                    <h3 class="dw-section-title">
                        Perbandingan dan Insight Inventori
                    </h3>

                    <p class="dw-section-caption">
                        Menjelaskan arah pergerakan barang, perubahan terhadap periode sebelumnya, dan kondisi yang perlu diperiksa.
                    </p>
                </div>
            </div>

            <div class="dw-kpi-grid">
                <div class="dw-kpi-card">
                    <div class="dw-kpi-label">Pergerakan Bersih</div>
                    <div class="dw-kpi-value {{ $advancedAnalytics['summary']['net_movement'] >= 0 ? 'dw-positive' : 'dw-negative' }}">
                        {{ $advancedAnalytics['summary']['net_movement'] > 0 ? '+' : '' }}{{ number_format($advancedAnalytics['summary']['net_movement'], 0, ',', '.') }}
                    </div>
                    <div class="dw-kpi-note">Qty masuk dikurangi qty keluar pada filter terpilih.</div>
                </div>

                <div class="dw-kpi-card">
                    <div class="dw-kpi-label">Perubahan Qty Masuk</div>
                    <div class="dw-kpi-value dw-info">
                        @if ($advancedAnalytics['summary']['qty_in_change'] === null)
                            -
                        @else
                            {{ $advancedAnalytics['summary']['qty_in_change'] > 0 ? '+' : '' }}{{ number_format($advancedAnalytics['summary']['qty_in_change'], 1, ',', '.') }}%
                        @endif
                    </div>
                    <div class="dw-kpi-note">{{ $advancedAnalytics['comparison_note'] }}</div>
                </div>

                <div class="dw-kpi-card">
                    <div class="dw-kpi-label">Perubahan Qty Keluar</div>
                    <div class="dw-kpi-value dw-info">
                        @if ($advancedAnalytics['summary']['qty_out_change'] === null)
                            -
                        @else
                            {{ $advancedAnalytics['summary']['qty_out_change'] > 0 ? '+' : '' }}{{ number_format($advancedAnalytics['summary']['qty_out_change'], 1, ',', '.') }}%
                        @endif
                    </div>
                    <div class="dw-kpi-note">{{ $advancedAnalytics['comparison_note'] }}</div>
                </div>

                <div class="dw-kpi-card">
                    <div class="dw-kpi-label">Klasifikasi Produk</div>
                    <div class="dw-kpi-value">
                        {{ number_format($advancedAnalytics['classification']['counts']['fast'], 0, ',', '.') }} / {{ number_format($advancedAnalytics['classification']['counts']['slow'], 0, ',', '.') }} / {{ number_format($advancedAnalytics['classification']['counts']['non_moving'], 0, ',', '.') }}
                    </div>
                    <div class="dw-kpi-note">Cepat / lambat / tidak bergerak berdasarkan qty keluar.</div>
                </div>
            </div>

            <div class="dw-insight-grid">
                @foreach ($advancedAnalytics['insights'] as $insight)
                    <article class="dw-insight-card" data-tone="{{ $insight['tone'] }}">
                        <span class="dw-insight-icon">{{ $insight['icon'] }}</span>

                        <div>
                            <h4 class="dw-insight-title">{{ $insight['title'] }}</h4>
                            <p class="dw-insight-body">{{ $insight['body'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        PERBANDINGAN GUDANG
                    </span>

                    <h3 class="dw-section-title">
                        Aktivitas Inventori per Gudang
                    </h3>

                    <p class="dw-section-caption">
                        Membandingkan qty masuk, qty keluar, pergerakan bersih, dan jumlah item yang perlu diperiksa pada setiap gudang.
                    </p>
                </div>
            </div>

            <div class="dw-widget-wrap">
                @livewire(
                    \App\Filament\Admin\DataWarehouseWidgets\DataWarehouseWarehouseComparisonChart::class,
                    [
                        'period' => $period,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'warehouseId' => $warehouseId,
                        'productId' => $this->productId,
                        'productCategory' => $this->productCategory,
                    ],
                    key('dw-warehouse-comparison-' . $period . '-' . $startDate . '-' . $endDate . '-' . $warehouseId . '-' . $this->productId . '-' . $this->productCategory)
                )
            </div>

            <div class="dw-table-card">
                <div class="dw-table-header">
                    <div>
                        <h4 class="dw-table-title">Rincian Perbandingan Gudang</h4>
                        <p class="dw-table-description">Tombol fokus menerapkan gudang tersebut sebagai filter dashboard.</p>
                    </div>
                </div>

                @if ($advancedAnalytics['warehouses'] === [])
                    <div class="dw-empty-state">Belum ada data gudang yang dapat dibandingkan.</div>
                @else
                    <div class="dw-table-scroll">
                        <table class="dw-table">
                            <thead>
                                <tr>
                                    <th>Gudang</th>
                                    <th class="dw-number">Qty Masuk</th>
                                    <th class="dw-number">Qty Keluar</th>
                                    <th class="dw-number">Pergerakan Bersih</th>
                                    <th class="dw-number">Perlu Diperiksa</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($advancedAnalytics['warehouses'] as $warehouse)
                                    <tr>
                                        <td><span class="dw-name">{{ $warehouse['name'] }}</span></td>
                                        <td class="dw-number">{{ number_format($warehouse['qty_in'], 0, ',', '.') }}</td>
                                        <td class="dw-number">{{ number_format($warehouse['qty_out'], 0, ',', '.') }}</td>
                                        <td class="dw-number {{ $warehouse['net_movement'] >= 0 ? 'dw-positive' : 'dw-negative' }}">
                                            {{ $warehouse['net_movement'] > 0 ? '+' : '' }}{{ number_format($warehouse['net_movement'], 0, ',', '.') }}
                                        </td>
                                        <td class="dw-number">{{ number_format($warehouse['attention_count'], 0, ',', '.') }}</td>
                                        <td>
                                            <button type="button" wire:click="$set('warehouseId', '{{ $warehouse['id'] }}')" class="dw-focus-btn">
                                                Fokus Gudang
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        KLASIFIKASI PERGERAKAN PRODUK
                    </span>

                    <h3 class="dw-section-title">
                        Produk Cepat, Lambat, dan Tidak Bergerak
                    </h3>

                    <p class="dw-section-caption">
                        {{ $advancedAnalytics['classification']['rule'] }}
                    </p>
                </div>
            </div>

            <div class="dw-table-card">
                <div class="dw-table-header">
                    <div>
                        <h4 class="dw-table-title">Sampel Produk per Klasifikasi</h4>
                        <p class="dw-table-description">
                            Rata-rata qty keluar produk aktif: {{ number_format($advancedAnalytics['classification']['average_active_qty_out'], 1, ',', '.') }}.
                            Ditampilkan maksimal lima produk dari setiap klasifikasi.
                        </p>
                    </div>
                </div>

                @if ($advancedAnalytics['classification']['rows'] === [])
                    <div class="dw-empty-state">Belum ada produk yang dapat diklasifikasikan.</div>
                @else
                    <div class="dw-table-scroll">
                        <table class="dw-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th>Klasifikasi</th>
                                    <th class="dw-number">Qty Keluar</th>
                                    <th class="dw-number">Frekuensi</th>
                                    <th>Terakhir Bergerak</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($advancedAnalytics['classification']['rows'] as $product)
                                    <tr>
                                        <td><span class="dw-name">{{ $product['name'] }}</span></td>
                                        <td>{{ $product['category'] }}</td>
                                        <td>
                                            <span class="dw-status dw-status-{{ $product['classification'] }}">
                                                {{ $product['classification_label'] }}
                                            </span>
                                        </td>
                                        <td class="dw-number">{{ number_format($product['total_qty_out'], 0, ',', '.') }}</td>
                                        <td class="dw-number">{{ number_format($product['movement_frequency'], 0, ',', '.') }}</td>
                                        <td>{{ $product['last_movement_date'] }}</td>
                                        <td>
                                            <button type="button" wire:click="$set('productId', '{{ $product['id'] }}')" class="dw-focus-btn">
                                                Fokus Produk
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        PERINGATAN STOK
                    </span>

                    <h3 class="dw-section-title">
                        Item yang Perlu Diperiksa
                    </h3>

                    <p class="dw-section-caption">
                        Menggunakan snapshot stok terakhir pada periode terpilih untuk menampilkan item berstatus menipis atau habis.
                    </p>
                </div>
            </div>

            <div class="dw-table-card">
                <div class="dw-table-header">
                    <div>
                        <h4 class="dw-table-title">Daftar Peringatan Stok</h4>
                        <p class="dw-table-description">Menampilkan maksimal 20 item dengan kondisi paling mendesak.</p>
                    </div>
                </div>

                @if ($advancedAnalytics['stock_alerts'] === [])
                    <div class="dw-empty-state">Tidak ada item berstatus menipis atau habis pada snapshot terakhir dalam filter terpilih.</div>
                @else
                    <div class="dw-table-scroll">
                        <table class="dw-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Gudang</th>
                                    <th class="dw-number">Stok Tersedia</th>
                                    <th class="dw-number">Batas Minimum</th>
                                    <th class="dw-number">Kekurangan</th>
                                    <th>Status</th>
                                    <th>Snapshot</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($advancedAnalytics['stock_alerts'] as $alert)
                                    <tr>
                                        <td>
                                            <span class="dw-name">{{ $alert['product_name'] }}</span><br>
                                            <span class="dw-muted">{{ $alert['category'] }}</span>
                                        </td>
                                        <td>{{ $alert['warehouse_name'] }}</td>
                                        <td class="dw-number">{{ number_format($alert['qty_available'], 0, ',', '.') }}</td>
                                        <td class="dw-number">{{ number_format($alert['minimum_stock'], 0, ',', '.') }}</td>
                                        <td class="dw-number dw-negative">{{ number_format($alert['shortage'], 0, ',', '.') }}</td>
                                        <td>
                                            <span class="dw-status dw-status-{{ $alert['status'] }}">
                                                {{ $alert['status_label'] }}
                                            </span>
                                        </td>
                                        <td>{{ $alert['snapshot_date'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        ANALISIS PERGERAKAN STOK
                    </span>

                    <h3 class="dw-section-title">
                        Grafik Analitik Inventori
                    </h3>

                    <p class="dw-section-caption">
                        Grafik ini menunjukkan tren kuantitas barang masuk dan keluar per bulan serta produk dengan
                        pergerakan keluar tertinggi.
                    </p>
                </div>
            </div>

            <div class="dw-chart-filter-grid">
                <div class="dw-field">
                    <label class="dw-label">
                        Tahun Grafik
                    </label>

                    <select wire:model.change="chartYear" class="dw-select">
                        <option value="">
                            Ikuti Filter Utama
                        </option>

                        @foreach ($this->getChartYearOptions() as $year => $label)
                            <option value="{{ $year }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="dw-chart-grid">
                <div class="dw-widget-wrap">
                    @livewire(
                        \App\Filament\Admin\DataWarehouseWidgets\DataWarehouseMovementTrendChart::class,
                        [
                            'period' => $period,
                            'startDate' => $startDate,
                            'endDate' => $endDate,
                            'warehouseId' => $warehouseId,
                            'chartYear' => $this->chartYear,
                            'productId' => $this->productId,
                            'productCategory' => $this->productCategory,
                        ],
                        key('dw-movement-trend-' . $period . '-' . $startDate . '-' . $endDate . '-' . $warehouseId . '-' . $this->chartYear . '-' . $this->productId . '-' . $this->productCategory)
                    )
                </div>

                <div class="dw-widget-wrap">
                    @livewire(
                        \App\Filament\Admin\DataWarehouseWidgets\DataWarehouseTopProductMovementChart::class,
                        [
                            'period' => $period,
                            'startDate' => $startDate,
                            'endDate' => $endDate,
                            'warehouseId' => $warehouseId,
                            'chartYear' => $this->chartYear,
                            'productId' => $this->productId,
                            'productCategory' => $this->productCategory,
                        ],
                        key('dw-top-product-movement-' . $period . '-' . $startDate . '-' . $endDate . '-' . $warehouseId . '-' . $this->chartYear . '-' . $this->productId . '-' . $this->productCategory)
                    )
                </div>
            </div>
        </section>

        <section class="dw-filter-card">
            <div class="dw-filter-header">
                <div>
                    <h3 class="dw-section-title">
                        Keterangan Sumber Data Warehouse
                    </h3>

                    <p class="dw-section-subtitle">
                        Data pada dashboard ini berasal dari tabel data warehouse yang telah melalui proses ETL,
                        bukan langsung dari tabel operasional.
                    </p>
                </div>
            </div>

            <div class="dw-source-grid">
                <div class="dw-field">
                    <label class="dw-label">
                        Tabel Fakta
                    </label>

                    <p class="dw-source-list">
                        dw_fact_inbound_transactions<br>
                        dw_fact_outbound_transactions<br>
                        dw_fact_inventory_movements<br>
                        dw_fact_stock_snapshots
                    </p>
                </div>

                <div class="dw-field">
                    <label class="dw-label">
                        Tabel Dimensi
                    </label>

                    <p class="dw-source-list">
                        dw_dim_products<br>
                        dw_dim_warehouses<br>
                        dw_dim_dates
                    </p>
                </div>

                <div class="dw-field">
                    <label class="dw-label">
                        Alur ETL
                    </label>

                    <p class="dw-source-list">
                        Data operasional → proses ETL → tabel dimensi dan fakta → dashboard analitik.
                    </p>
                </div>

                <div class="dw-field">
                    <label class="dw-label">
                        Fungsi Analitik
                    </label>

                    <p class="dw-source-list">
                        Mendukung analisis historis inventori, pergerakan stok, status stok, dan pemantauan kegiatan gudang.
                    </p>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>