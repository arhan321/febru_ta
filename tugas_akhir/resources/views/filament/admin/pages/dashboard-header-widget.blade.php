<x-filament-widgets::widget>
    <style>
        .nsa-hero {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            padding: 34px;
            background:
                radial-gradient(circle at top right, rgba(255,255,255,.22), transparent 28%),
                linear-gradient(135deg, #10b981 0%, #0d9488 48%, #0891b2 100%);
            color: #ffffff;
            box-shadow: 0 18px 45px rgba(15, 118, 110, .22);
        }

        .nsa-hero::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            right: -80px;
            top: -100px;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
        }

        .nsa-hero::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: 180px;
            bottom: -140px;
            border-radius: 999px;
            background: rgba(255,255,255,.10);
        }

        .nsa-hero-content {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(360px, .9fr);
            gap: 28px;
            align-items: center;
        }

        .nsa-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,.18);
            backdrop-filter: blur(8px);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .nsa-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #ffffff;
            box-shadow: 0 0 0 5px rgba(255,255,255,.16);
        }

        .nsa-title {
            margin: 0;
            max-width: 850px;
            font-size: 34px;
            line-height: 1.16;
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .nsa-subtitle {
            margin-top: 14px;
            max-width: 850px;
            color: rgba(255,255,255,.88);
            font-size: 15px;
            line-height: 1.7;
        }

        .nsa-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .nsa-meta-card {
            min-width: 160px;
            padding: 13px 16px;
            border-radius: 18px;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.18);
            backdrop-filter: blur(10px);
        }

        .nsa-meta-label {
            margin: 0;
            font-size: 11px;
            color: rgba(255,255,255,.72);
        }

        .nsa-meta-value {
            margin: 4px 0 0;
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
        }

        .nsa-summary-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .nsa-summary-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 18px;
            border-radius: 22px;
            background: rgba(255,255,255,.95);
            color: #0f172a;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .10);
        }

        .nsa-summary-label {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
        }

        .nsa-summary-value {
            margin: 6px 0 0;
            font-size: 30px;
            line-height: 1;
            font-weight: 800;
            color: #020617;
        }

        .nsa-summary-desc {
            margin: 6px 0 0;
            font-size: 12px;
            color: #64748b;
        }

        .nsa-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 20px;
        }

        .nsa-icon-green {
            background: #dcfce7;
            color: #16a34a;
        }

        .nsa-icon-blue {
            background: #dbeafe;
            color: #2563eb;
        }

        .nsa-icon-orange {
            background: #ffedd5;
            color: #ea580c;
        }

        @media (min-width: 1280px) {
            .nsa-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1100px) {
            .nsa-hero-content {
                grid-template-columns: 1fr;
            }

            .nsa-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .nsa-hero {
                padding: 24px;
                border-radius: 22px;
            }

            .nsa-title {
                font-size: 26px;
            }

            .nsa-summary-grid {
                grid-template-columns: 1fr;
            }

            .nsa-meta-card {
                width: 100%;
            }
        }
    </style>

    <div class="nsa-hero">
        <div class="nsa-hero-content">
            <div>
                <div class="nsa-badge">
                    <span class="nsa-badge-dot"></span>
                    Dashboard Inventory
                </div>

                <h1 class="nsa-title">
                    Selamat Datang di PT. Naura Sukses Abadi
                </h1>

                <p class="nsa-subtitle">
                    Pantau stok produk, transaksi barang masuk dan keluar, approval inventory,
                    serta sinkronisasi data warehouse dalam satu halaman dashboard yang ringkas.
                </p>

                <div class="nsa-meta">
                    <div class="nsa-meta-card">
                        <p class="nsa-meta-label">Tanggal Hari Ini</p>
                        <p class="nsa-meta-value">{{ $today }}</p>
                    </div>

                    <div class="nsa-meta-card">
                        <p class="nsa-meta-label">Status Sistem</p>
                        <p class="nsa-meta-value">Aktif</p>
                    </div>

                    <div class="nsa-meta-card">
                        <p class="nsa-meta-label">Periode Dashboard</p>
                        <p class="nsa-meta-value">Tanggal Transaksi</p>
                    </div>
                </div>
            </div>

            <div class="nsa-summary-grid">
                <div class="nsa-summary-card">
                    <div>
                        <p class="nsa-summary-label">Total Produk</p>
                        <p class="nsa-summary-value">
                            {{ number_format($totalProducts, 0, ',', '.') }}
                        </p>
                        <p class="nsa-summary-desc">Master produk jadi</p>
                    </div>
                    <div class="nsa-icon nsa-icon-green">📦</div>
                </div>

                <div class="nsa-summary-card">
                    <div>
                        <p class="nsa-summary-label">Masuk Bulan Ini</p>
                        <p class="nsa-summary-value">
                            {{ number_format($inboundThisMonth, 0, ',', '.') }}
                        </p>
                        <p class="nsa-summary-desc">Transaksi masuk approved</p>
                    </div>
                    <div class="nsa-icon nsa-icon-blue">⬇️</div>
                </div>

                <div class="nsa-summary-card">
                    <div>
                        <p class="nsa-summary-label">Keluar Bulan Ini</p>
                        <p class="nsa-summary-value">
                            {{ number_format($outboundThisMonth, 0, ',', '.') }}
                        </p>
                        <p class="nsa-summary-desc">Transaksi keluar approved</p>
                    </div>
                    <div class="nsa-icon nsa-icon-orange">⬆️</div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>