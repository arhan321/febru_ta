<x-filament-panels::page>
    @php
        $run = $this->getEtlRun();
        $steps = $run->details;

        $executor = $run->trigger_type === 'scheduler'
            ? 'Sistem'
            : ($run->triggeredByUser?->name ?? 'CLI / Sistem');

        $triggerLabel = $run->trigger_type === 'scheduler'
            ? 'Scheduler'
            : 'Manual';

        $statusLabel = match ($run->status) {
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            'running' => 'Diproses',
            default => ucfirst((string) $run->status),
        };

        $statusClass = match ($run->status) {
            'success' => 'etl-main-success',
            'failed' => 'etl-main-failed',
            'running' => 'etl-main-running',
            default => 'etl-main-default',
        };

        $successCount = $steps->where('status', 'success')->count();
        $failedCount = $steps->where('status', 'failed')->count();
        $rollbackCount = $steps->where('rolled_back', true)->count();
        $skippedCount = $steps->where('status', 'skipped')->count();

        $sourceRows = (int) ($run->source_rows ?? 0);
        $targetRows = (int) ($run->target_rows ?? 0);

        $rowsMatched =
            $run->status === 'success'
            && $sourceRows === $targetRows;

        $durationSeconds = $run->duration_ms !== null
            ? $run->duration_ms / 1000
            : null;

        $successPercentage = $steps->count() > 0
            ? round(($successCount / $steps->count()) * 100)
            : 0;
    @endphp

    <style>
        /* =========================================================
         * PREMIUM ETL DETAIL
         * ======================================================= */

        .etl-page {
            --etl-primary: #2563eb;
            --etl-primary-dark: #1d4ed8;
            --etl-indigo: #4f46e5;
            --etl-green: #059669;
            --etl-red: #dc2626;
            --etl-amber: #d97706;
            --etl-text: #0f172a;
            --etl-muted: #64748b;
            --etl-border: #e2e8f0;
            --etl-soft: #f8fafc;

            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            padding: 10px 10px 52px;

            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* =========================================================
         * TOP NAVIGATION
         * ======================================================= */

        .etl-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .etl-back {
            display: inline-flex;
            align-items: center;
            gap: 9px;

            min-height: 40px;
            padding: 9px 14px;

            border: 1px solid #e2e8f0;
            border-radius: 12px;

            background: rgba(255, 255, 255, .9);

            color: #475569;
            font-size: 12px;
            font-weight: 850;
            text-decoration: none;

            box-shadow:
                0 5px 15px rgba(15, 23, 42, .035);

            transition:
                all .18s ease;
        }

        .etl-back:hover {
            color: #1d4ed8;
            border-color: #bfdbfe;
            background: #eff6ff;
            transform: translateX(-2px);
        }

        .etl-top-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 800;
        }

        /* =========================================================
         * HERO
         * ======================================================= */

        .etl-hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;

            border-radius: 30px;
            border: 1px solid rgba(148, 163, 184, .22);

            background:
                radial-gradient(
                    circle at 0% 0%,
                    rgba(37, 99, 235, .22),
                    transparent 34%
                ),
                radial-gradient(
                    circle at 100% 100%,
                    rgba(5, 150, 105, .17),
                    transparent 34%
                ),
                linear-gradient(
                    135deg,
                    #ffffff 0%,
                    #f8fbff 52%,
                    #f4fdf9 100%
                );

            box-shadow:
                0 25px 60px rgba(15, 23, 42, .09),
                inset 0 1px 0 rgba(255, 255, 255, .85);
        }

        .etl-hero::before {
            content: "";
            position: absolute;

            width: 320px;
            height: 320px;

            top: -220px;
            right: 15%;

            border-radius: 999px;

            background: rgba(79, 70, 229, .10);

            z-index: -1;
        }

        .etl-hero::after {
            content: "";

            position: absolute;

            width: 250px;
            height: 250px;

            right: -120px;
            bottom: -160px;

            border-radius: 999px;

            background: rgba(5, 150, 105, .10);

            z-index: -1;
        }

        .etl-hero-content {
            padding: 31px;

            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                280px;

            align-items: center;
            gap: 35px;
        }

        .etl-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 7px 12px;

            border-radius: 999px;
            border: 1px solid #dbeafe;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fbff
                );

            color: #1d4ed8;

            font-size: 10px;
            line-height: 1;
            font-weight: 950;

            letter-spacing: .065em;
            text-transform: uppercase;
        }

        .etl-title {
            margin: 15px 0 0;

            color: #0f172a;

            font-size: clamp(
                28px,
                3vw,
                38px
            );

            line-height: 1.1;

            letter-spacing: -.035em;

            font-weight: 950;
        }

        .etl-description {
            max-width: 750px;

            margin: 12px 0 0;

            color: #64748b;

            font-size: 13px;
            line-height: 1.7;
        }

        .etl-batch-box {
            margin-top: 20px;

            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .etl-batch {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 9px 12px;

            border: 1px solid #dbe3ed;
            border-radius: 11px;

            background: rgba(
                255,
                255,
                255,
                .85
            );

            box-shadow:
                0 6px 18px
                rgba(15, 23, 42, .035);

            color: #334155;

            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;

            font-size: 11px;

            font-weight: 850;
        }

        .etl-trigger-pill {
            display: inline-flex;
            align-items: center;

            padding: 9px 12px;

            border-radius: 11px;

            background:
                rgba(79, 70, 229, .08);

            border:
                1px solid
                rgba(79, 70, 229, .15);

            color: #4338ca;

            font-size: 10px;
            font-weight: 900;
        }

        /* =========================================================
         * HERO STATUS PANEL
         * ======================================================= */

        .etl-hero-status {
            padding: 20px;

            border:
                1px solid
                rgba(255, 255, 255, .8);

            border-radius: 20px;

            background:
                rgba(255, 255, 255, .76);

            backdrop-filter: blur(10px);

            box-shadow:
                0 15px 34px
                rgba(15, 23, 42, .06);
        }

        .etl-status-caption {
            color: #94a3b8;

            font-size: 9px;

            font-weight: 950;

            letter-spacing: .07em;

            text-transform: uppercase;
        }

        .etl-main-status {
            margin-top: 10px;

            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 8px 12px;

            border-radius: 999px;

            font-size: 11px;
            font-weight: 950;
        }

        .etl-main-status::before {
            content: "";

            width: 7px;
            height: 7px;

            border-radius: 999px;

            background: currentColor;

            box-shadow:
                0 0 0 4px
                rgba(255, 255, 255, .7);
        }

        .etl-main-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
        }

        .etl-main-failed {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
        }

        .etl-main-running {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #b45309;
        }

        .etl-main-default {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
        }

        .etl-progress-header {
            margin-top: 18px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 10px;

            color: #475569;

            font-size: 10px;
            font-weight: 850;
        }

        .etl-progress-track {
            height: 7px;

            margin-top: 8px;

            overflow: hidden;

            border-radius: 999px;

            background: #e2e8f0;
        }

        .etl-progress-bar {
            height: 100%;

            border-radius: inherit;

            background:
                linear-gradient(
                    90deg,
                    #2563eb,
                    #4f46e5,
                    #059669
                );

            box-shadow:
                0 0 14px
                rgba(37, 99, 235, .24);
        }

        .etl-hero-status-note {
            margin-top: 12px;

            color: #94a3b8;

            font-size: 9px;
            line-height: 1.5;
        }

        /* =========================================================
         * KPI
         * ======================================================= */

        .etl-kpi-grid {
            display: grid;

            grid-template-columns:
                repeat(6, minmax(0, 1fr));

            gap: 13px;
        }

        .etl-kpi {
            position: relative;
            overflow: hidden;

            min-height: 122px;

            padding: 17px;

            border:
                1px solid
                #e2e8f0;

            border-radius: 19px;

            background: #ffffff;

            box-shadow:
                0 10px 27px
                rgba(15, 23, 42, .048);

            transition:
                transform .18s ease,
                box-shadow .18s ease;
        }

        .etl-kpi:hover {
            transform:
                translateY(-3px);

            box-shadow:
                0 17px 35px
                rgba(15, 23, 42, .075);
        }

        .etl-kpi::before {
            content: "";

            position: absolute;

            top: 0;
            left: 16px;
            right: 16px;

            height: 3px;

            border-radius:
                0 0 999px 999px;

            background:
                linear-gradient(
                    90deg,
                    #2563eb,
                    #60a5fa
                );
        }

        .etl-kpi:nth-child(2)::before {
            background:
                linear-gradient(
                    90deg,
                    #059669,
                    #34d399
                );
        }

        .etl-kpi:nth-child(3)::before {
            background:
                linear-gradient(
                    90deg,
                    #dc2626,
                    #fb7185
                );
        }

        .etl-kpi:nth-child(4)::before {
            background:
                linear-gradient(
                    90deg,
                    #d97706,
                    #fbbf24
                );
        }

        .etl-kpi:nth-child(5)::before {
            background:
                linear-gradient(
                    90deg,
                    #64748b,
                    #94a3b8
                );
        }

        .etl-kpi:nth-child(6)::before {
            background:
                linear-gradient(
                    90deg,
                    #4f46e5,
                    #8b5cf6
                );
        }

        .etl-kpi-label {
            color: #94a3b8;

            font-size: 9px;

            font-weight: 950;

            letter-spacing: .07em;

            text-transform: uppercase;
        }

        .etl-kpi-value {
            margin-top: 11px;

            color: #0f172a;

            font-size: 23px;
            line-height: 1;

            letter-spacing: -.025em;

            font-weight: 950;
        }

        .etl-kpi-note {
            margin-top: 9px;

            color: #94a3b8;

            font-size: 9px;
            line-height: 1.45;
        }

        .etl-positive {
            color: #059669 !important;
        }

        .etl-negative {
            color: #dc2626 !important;
        }

        .etl-warning {
            color: #d97706 !important;
        }

        .etl-info {
            color: #2563eb !important;
        }

        /* =========================================================
         * SECTION CARDS
         * ======================================================= */

        .etl-card {
            overflow: hidden;

            border:
                1px solid
                #e2e8f0;

            border-radius: 23px;

            background: #ffffff;

            box-shadow:
                0 12px 32px
                rgba(15, 23, 42, .05);
        }

        .etl-card-header {
            padding: 18px 21px;

            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            gap: 16px;

            border-bottom:
                1px solid
                #e2e8f0;

            background:
                radial-gradient(
                    circle at top right,
                    rgba(37, 99, 235, .06),
                    transparent 32%
                ),
                linear-gradient(
                    180deg,
                    #ffffff,
                    #fbfdff
                );
        }

        .etl-card-title {
            margin: 0;

            color: #0f172a;

            font-size: 15px;

            font-weight: 950;
        }

        .etl-card-desc {
            margin: 5px 0 0;

            color: #94a3b8;

            font-size: 10px;
            line-height: 1.55;
        }

        .etl-card-counter {
            flex: 0 0 auto;

            padding: 7px 10px;

            border-radius: 999px;

            border:
                1px solid
                #dbeafe;

            background: #eff6ff;

            color: #1d4ed8;

            font-size: 9px;
            font-weight: 950;
        }

        /* =========================================================
         * BATCH INFO
         * ======================================================= */

        .etl-info-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));
        }

        .etl-info-item {
            position: relative;

            min-height: 100px;

            padding: 17px 19px;

            border-right:
                1px solid
                #f1f5f9;

            border-bottom:
                1px solid
                #f1f5f9;
        }

        .etl-info-label {
            color: #94a3b8;

            font-size: 8px;

            font-weight: 950;

            letter-spacing: .07em;

            text-transform: uppercase;
        }

        .etl-info-value {
            margin-top: 8px;

            color: #334155;

            font-size: 12px;

            line-height: 1.5;

            font-weight: 900;
        }

        .etl-info-sub {
            margin-top: 3px;

            color: #94a3b8;

            font-size: 9px;
        }

        /* =========================================================
         * ERROR
         * ======================================================= */

        .etl-error {
            padding: 18px;

            display: grid;

            grid-template-columns:
                42px minmax(0, 1fr);

            gap: 13px;

            border:
                1px solid
                #fecdd3;

            border-radius: 18px;

            background:
                linear-gradient(
                    135deg,
                    #fff1f2,
                    #fffafa
                );
        }

        .etl-error-icon {
            width: 42px;
            height: 42px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            border-radius: 12px;

            background: #ffe4e6;

            color: #be123c;

            font-size: 18px;
            font-weight: 950;
        }

        .etl-error-title {
            margin: 0;

            color: #be123c;

            font-size: 12px;

            font-weight: 950;
        }

        .etl-error-message {
            margin: 6px 0 0;

            color: #9f1239;

            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;

            font-size: 10px;
            line-height: 1.6;

            word-break: break-word;
        }

        /* =========================================================
         * ETL PIPELINE
         * ======================================================= */

        .etl-pipeline {
            padding: 20px;

            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .etl-step {
            position: relative;

            display: grid;

            grid-template-columns:
                52px
                minmax(220px, 1.25fr)
                minmax(120px, .55fr)
                minmax(275px, 1fr)
                minmax(130px, .55fr)
                minmax(185px, .8fr);

            align-items: center;

            gap: 15px;

            min-height: 92px;

            padding: 14px 16px;

            border:
                1px solid
                #e8edf4;

            border-radius: 17px;

            background:
                linear-gradient(
                    135deg,
                    #ffffff,
                    #fbfdff
                );

            box-shadow:
                0 6px 17px
                rgba(15, 23, 42, .025);

            transition:
                transform .18s ease,
                border-color .18s ease,
                box-shadow .18s ease;
        }

        .etl-step:hover {
            transform:
                translateY(-2px);

            border-color:
                #bfdbfe;

            box-shadow:
                0 11px 26px
                rgba(37, 99, 235, .07);
        }

        .etl-step-number {
            width: 42px;
            height: 42px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #dbeafe
                );

            color: #1d4ed8;

            font-size: 13px;
            font-weight: 950;

            box-shadow:
                inset 0 0 0 1px
                rgba(37, 99, 235, .08);
        }

        .etl-step-title {
            color: #1e293b;

            font-size: 12px;

            font-weight: 950;
        }

        .etl-step-key {
            display: block;

            margin-top: 4px;

            color: #94a3b8;

            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;

            font-size: 8px;
        }

        .etl-step-badges {
            margin-top: 7px;

            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .etl-type,
        .etl-step-status {
            display: inline-flex;
            align-items: center;

            padding: 5px 8px;

            border-radius: 999px;

            font-size: 8px;
            line-height: 1;

            font-weight: 950;

            white-space: nowrap;
        }

        .etl-type-dimension {
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .etl-type-fact {
            border: 1px solid #d1fae5;
            background: #ecfdf5;
            color: #047857;
        }

        .etl-step-success {
            border: 1px solid #d1fae5;
            background: #ecfdf5;
            color: #047857;
        }

        .etl-step-failed {
            border: 1px solid #fecdd3;
            background: #fff1f2;
            color: #be123c;
        }

        .etl-step-processing {
            border: 1px solid #fde68a;
            background: #fffbeb;
            color: #b45309;
        }

        .etl-step-rollback {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        .etl-step-skipped {
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
            color: #64748b;
        }

        .etl-step-default {
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
            color: #475569;
        }

        /* =========================================================
         * SOURCE TARGET FLOW
         * ======================================================= */

        .etl-row-flow {
            display: grid;

            grid-template-columns:
                1fr auto 1fr;

            align-items: center;

            gap: 7px;
        }

        .etl-row-box {
            padding: 9px 8px;

            border-radius: 10px;

            background: #f8fafc;

            border: 1px solid #e2e8f0;

            text-align: center;
        }

        .etl-row-label {
            display: block;

            color: #94a3b8;

            font-size: 7px;

            font-weight: 950;

            letter-spacing: .05em;

            text-transform: uppercase;
        }

        .etl-row-value {
            display: block;

            margin-top: 4px;

            color: #0f172a;

            font-size: 12px;

            font-weight: 950;

            font-variant-numeric:
                tabular-nums;
        }

        .etl-flow-arrow {
            color: #94a3b8;

            font-size: 14px;

            font-weight: 900;
        }

        /* =========================================================
         * DIFFERENCE
         * ======================================================= */

        .etl-difference {
            text-align: center;
        }

        .etl-difference-label {
            color: #94a3b8;

            font-size: 7px;

            font-weight: 950;

            letter-spacing: .05em;

            text-transform: uppercase;
        }

        .etl-difference-value {
            margin-top: 5px;

            color: #059669;

            font-size: 15px;

            font-weight: 950;
        }

        /* =========================================================
         * DURATION
         * ======================================================= */

        .etl-duration-label {
            color: #94a3b8;

            font-size: 7px;

            font-weight: 950;

            letter-spacing: .05em;

            text-transform: uppercase;
        }

        .etl-duration-value {
            margin-top: 5px;

            display: flex;
            align-items: center;
            gap: 5px;

            color: #334155;

            font-size: 10px;

            font-weight: 900;
        }

        /* =========================================================
         * TIME
         * ======================================================= */

        .etl-time {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .etl-time-row {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 8px;

            font-size: 8px;
        }

        .etl-time-label {
            color: #94a3b8;

            font-weight: 850;
        }

        .etl-time-value {
            color: #475569;

            font-weight: 850;

            white-space: nowrap;
        }

        /* =========================================================
         * ROLLBACK + ERROR
         * ======================================================= */

        .etl-rollback-note {
            display: inline-flex;

            margin-top: 6px;

            padding: 4px 6px;

            border-radius: 7px;

            background: #fff1f2;

            color: #be123c;

            font-size: 7px;

            font-weight: 950;
        }

        .etl-step-error {
            margin-top: 6px;

            max-width: 500px;

            color: #be123c;

            font-size: 8px;
            line-height: 1.5;

            word-break: break-word;
        }

        /* =========================================================
         * FOOTNOTE
         * ======================================================= */

        .etl-footer-note {
            padding: 15px 18px;

            display: flex;
            align-items: center;
            gap: 10px;

            border-top:
                1px solid
                #e2e8f0;

            background: #f8fafc;

            color: #64748b;

            font-size: 9px;
            line-height: 1.5;
        }

        .etl-footer-icon {
            width: 27px;
            height: 27px;

            flex: 0 0 auto;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background: #eff6ff;

            color: #1d4ed8;

            font-weight: 950;
        }

        /* =========================================================
         * DARK MODE
         * ======================================================= */

        .dark .etl-page {
            --etl-text: #f8fafc;
            --etl-muted: #94a3b8;
            --etl-border: #334155;
            --etl-soft: #0f172a;
        }

        .dark .etl-back,
        .dark .etl-kpi,
        .dark .etl-card {
            border-color: #334155;

            background: #111827;

            color: #e2e8f0;

            box-shadow:
                0 15px 36px
                rgba(0, 0, 0, .15);
        }

        .dark .etl-hero {
            border-color: #334155;

            background:
                radial-gradient(
                    circle at 0% 0%,
                    rgba(37, 99, 235, .25),
                    transparent 35%
                ),
                radial-gradient(
                    circle at 100% 100%,
                    rgba(5, 150, 105, .15),
                    transparent 34%
                ),
                linear-gradient(
                    135deg,
                    #0f172a,
                    #111827
                );
        }

        .dark .etl-hero-status {
            border-color:
                rgba(71, 85, 105, .8);

            background:
                rgba(15, 23, 42, .72);
        }

        .dark .etl-title,
        .dark .etl-kpi-value,
        .dark .etl-card-title,
        .dark .etl-step-title,
        .dark .etl-row-value {
            color: #f8fafc;
        }

        .dark .etl-description,
        .dark .etl-card-desc {
            color: #94a3b8;
        }

        .dark .etl-batch {
            border-color: #334155;

            background: #0f172a;

            color: #e2e8f0;
        }

        .dark .etl-card-header {
            border-color: #334155;

            background:
                radial-gradient(
                    circle at top right,
                    rgba(37, 99, 235, .08),
                    transparent 34%
                ),
                #0f172a;
        }

        .dark .etl-info-item {
            border-color: #1e293b;
        }

        .dark .etl-info-value {
            color: #e2e8f0;
        }

        .dark .etl-step {
            border-color: #334155;

            background:
                linear-gradient(
                    135deg,
                    #111827,
                    #0f172a
                );
        }

        .dark .etl-step:hover {
            border-color: #3b82f6;

            box-shadow:
                0 10px 28px
                rgba(37, 99, 235, .09);
        }

        .dark .etl-row-box {
            border-color: #334155;

            background: #0f172a;
        }

        .dark .etl-duration-value,
        .dark .etl-time-value {
            color: #cbd5e1;
        }

        .dark .etl-footer-note {
            border-color: #334155;

            background: #0f172a;

            color: #94a3b8;
        }

        /* =========================================================
         * RESPONSIVE
         * ======================================================= */

        @media (max-width: 1250px) {
            .etl-kpi-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

            .etl-step {
                grid-template-columns:
                    45px
                    minmax(200px, 1fr)
                    minmax(110px, .5fr)
                    minmax(240px, 1fr);

                align-items: start;
            }

            .etl-difference,
            .etl-time {
                grid-column: auto;
            }
        }

        @media (max-width: 1024px) {
            .etl-hero-content {
                grid-template-columns: 1fr;
            }

            .etl-info-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .etl-step {
                grid-template-columns:
                    45px
                    minmax(0, 1fr)
                    minmax(190px, .75fr);
            }

            .etl-step > :nth-child(4),
            .etl-step > :nth-child(5),
            .etl-step > :nth-child(6) {
                grid-column:
                    span 1;
            }
        }

        @media (max-width: 768px) {
            .etl-page {
                padding-left: 3px;
                padding-right: 3px;

                gap: 19px;
            }

            .etl-topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .etl-top-label {
                display: none;
            }

            .etl-hero {
                border-radius: 23px;
            }

            .etl-hero-content {
                padding: 22px;
            }

            .etl-title {
                font-size: 26px;
            }

            .etl-kpi-grid,
            .etl-info-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .etl-info-item {
                min-height: 90px;
            }

            .etl-pipeline {
                padding: 13px;
            }

            .etl-step {
                grid-template-columns:
                    42px minmax(0, 1fr);

                gap: 12px;
            }

            .etl-step > *:nth-child(n + 3) {
                grid-column:
                    1 / -1;
            }

            .etl-row-flow {
                max-width: 320px;
            }

            .etl-time-row {
                justify-content: flex-start;

                gap: 15px;
            }
        }

        @media (max-width: 520px) {
            .etl-kpi-grid {
                grid-template-columns: 1fr;
            }

            .etl-kpi {
                min-height: auto;
            }

            .etl-info-grid {
                grid-template-columns: 1fr;
            }

            .etl-hero-status {
                width: 100%;
            }
        }
    </style>

    <div class="etl-page">

        {{-- =====================================================
            TOP BAR
        ====================================================== --}}
        <div class="etl-topbar">
            <a
                href="{{ \App\Filament\Admin\Pages\DataWarehouseDashboard::getUrl() }}"
                class="etl-back"
            >
                ← Kembali ke Analitik Inventori
            </a>

            <span class="etl-top-label">
                Data Warehouse / Monitoring ETL / Detail Batch
            </span>
        </div>

        {{-- =====================================================
            HERO
        ====================================================== --}}
        <section class="etl-hero">
            <div class="etl-hero-content">
                <div>
                    <span class="etl-kicker">
                        ⚙ ETL Process Monitoring
                    </span>

                    <h1 class="etl-title">
                        Detail Proses ETL
                    </h1>

                    <p class="etl-description">
                        Monitoring lengkap proses ekstraksi, transformasi,
                        dan pemuatan data inventori dari database operasional
                        menuju tabel dimensi dan fakta pada Data Warehouse.
                    </p>

                    <div class="etl-batch-box">
                        <span class="etl-batch">
                            # {{ $run->batch_code ?? 'Tanpa Kode Batch' }}
                        </span>

                        <span class="etl-trigger-pill">
                            {{ $triggerLabel }}
                        </span>
                    </div>
                </div>

                <aside class="etl-hero-status">
                    <div class="etl-status-caption">
                        Status Eksekusi
                    </div>

                    <span class="etl-main-status {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>

                    <div class="etl-progress-header">
                        <span>
                            Progress Tahapan
                        </span>

                        <strong>
                            {{ $successPercentage }}%
                        </strong>
                    </div>

                    <div class="etl-progress-track">
                        <div
                            class="etl-progress-bar"
                            style="width: {{ $successPercentage }}%;"
                        ></div>
                    </div>

                    <p class="etl-hero-status-note">
                        {{ $successCount }} dari {{ $steps->count() }}
                        tahapan berhasil diproses pada batch ini.
                    </p>
                </aside>
            </div>
        </section>

        {{-- =====================================================
            KPI
        ====================================================== --}}
        <section class="etl-kpi-grid">

            <article class="etl-kpi">
                <div class="etl-kpi-label">
                    Total Tahap
                </div>

                <div class="etl-kpi-value etl-info">
                    {{ $steps->count() }}
                </div>

                <div class="etl-kpi-note">
                    Seluruh tahapan ETL yang dimonitor.
                </div>
            </article>

            <article class="etl-kpi">
                <div class="etl-kpi-label">
                    Berhasil
                </div>

                <div class="etl-kpi-value etl-positive">
                    {{ $successCount }}
                </div>

                <div class="etl-kpi-note">
                    Tahapan yang selesai tanpa kegagalan.
                </div>
            </article>

            <article class="etl-kpi">
                <div class="etl-kpi-label">
                    Gagal
                </div>

                <div
                    class="etl-kpi-value
                    {{ $failedCount > 0 ? 'etl-negative' : 'etl-positive' }}"
                >
                    {{ $failedCount }}
                </div>

                <div class="etl-kpi-note">
                    Tahapan yang mengalami kegagalan.
                </div>
            </article>

            <article class="etl-kpi">
                <div class="etl-kpi-label">
                    Rollback
                </div>

                <div
                    class="etl-kpi-value
                    {{ $rollbackCount > 0 ? 'etl-warning' : '' }}"
                >
                    {{ $rollbackCount }}
                </div>

                <div class="etl-kpi-note">
                    Tahapan yang dibatalkan transaksi.
                </div>
            </article>

            <article class="etl-kpi">
                <div class="etl-kpi-label">
                    Dilewati
                </div>

                <div class="etl-kpi-value">
                    {{ $skippedCount }}
                </div>

                <div class="etl-kpi-note">
                    Tahapan yang tidak dijalankan.
                </div>
            </article>

            <article class="etl-kpi">
                <div class="etl-kpi-label">
                    Source vs Target
                </div>

                <div
                    class="etl-kpi-value
                    {{ $rowsMatched ? 'etl-positive' : 'etl-warning' }}"
                >
                    {{ $rowsMatched ? 'Sesuai' : 'Periksa' }}
                </div>

                <div class="etl-kpi-note">
                    Hasil rekonsiliasi jumlah baris.
                </div>
            </article>

        </section>

        {{-- =====================================================
            BATCH INFORMATION
        ====================================================== --}}
        <section class="etl-card">
            <div class="etl-card-header">
                <div>
                    <h2 class="etl-card-title">
                        Informasi Batch ETL
                    </h2>

                    <p class="etl-card-desc">
                        Metadata eksekusi dan ringkasan proses sinkronisasi.
                    </p>
                </div>

                <span class="etl-card-counter">
                    Batch #{{ $run->id }}
                </span>
            </div>

            <div class="etl-info-grid">

                <div class="etl-info-item">
                    <div class="etl-info-label">
                        Pemicu Proses
                    </div>

                    <div class="etl-info-value">
                        {{ $triggerLabel }}
                    </div>

                    <div class="etl-info-sub">
                        {{ $run->trigger_type === 'scheduler'
                            ? 'Eksekusi otomatis terjadwal'
                            : 'Eksekusi manual' }}
                    </div>
                </div>

                <div class="etl-info-item">
                    <div class="etl-info-label">
                        Dijalankan Oleh
                    </div>

                    <div class="etl-info-value">
                        {{ $executor }}
                    </div>

                    <div class="etl-info-sub">
                        Pelaksana proses ETL
                    </div>
                </div>

                <div class="etl-info-item">
                    <div class="etl-info-label">
                        Total Baris Sumber
                    </div>

                    <div class="etl-info-value">
                        {{ number_format(
                            $sourceRows,
                            0,
                            ',',
                            '.'
                        ) }}
                    </div>

                    <div class="etl-info-sub">
                        Record yang dibaca dari sumber
                    </div>
                </div>

                <div class="etl-info-item">
                    <div class="etl-info-label">
                        Total Baris Target
                    </div>

                    <div class="etl-info-value">
                        {{ number_format(
                            $targetRows,
                            0,
                            ',',
                            '.'
                        ) }}
                    </div>

                    <div class="etl-info-sub">
                        Record hasil pemuatan DW
                    </div>
                </div>

                <div class="etl-info-item">
                    <div class="etl-info-label">
                        Waktu Mulai
                    </div>

                    <div class="etl-info-value">
                        {{ $run->created_at?->format(
                            'd M Y H:i:s'
                        ) ?? '-' }}
                    </div>
                </div>

                <div class="etl-info-item">
                    <div class="etl-info-label">
                        Waktu Selesai
                    </div>

                    <div class="etl-info-value">
                        {{ $run->finished_at?->format(
                            'd M Y H:i:s'
                        ) ?? '-' }}
                    </div>
                </div>

                <div class="etl-info-item">
                    <div class="etl-info-label">
                        Durasi Total
                    </div>

                    <div class="etl-info-value">
                        @if ($durationSeconds !== null)
                            {{ number_format(
                                $durationSeconds,
                                2,
                                ',',
                                '.'
                            ) }}
                            detik
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="etl-info-item">
                    <div class="etl-info-label">
                        Status Batch
                    </div>

                    <div class="etl-info-value">
                        <span class="etl-main-status {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

            </div>
        </section>

        {{-- =====================================================
            ERROR
        ====================================================== --}}
        @if ($run->error_message)
            <section class="etl-error">
                <span class="etl-error-icon">
                    !
                </span>

                <div>
                    <h3 class="etl-error-title">
                        Kegagalan Proses ETL
                    </h3>

                    <p class="etl-error-message">
                        {{ $run->error_message }}
                    </p>
                </div>
            </section>
        @endif

        {{-- =====================================================
            PIPELINE
        ====================================================== --}}
        <section class="etl-card">
            <div class="etl-card-header">
                <div>
                    <h2 class="etl-card-title">
                        Pipeline Tahapan ETL
                    </h2>

                    <p class="etl-card-desc">
                        Rincian lima pemrosesan tabel dimensi dan empat
                        tabel fakta inventori pada batch ini.
                    </p>
                </div>

                <span class="etl-card-counter">
                    {{ $steps->count() }} Tahap
                </span>
            </div>

            @if ($steps->isEmpty())
                <div
                    style="
                        padding: 40px 20px;
                        color: #94a3b8;
                        font-size: 12px;
                        text-align: center;
                    "
                >
                    Belum terdapat detail tahapan untuk batch ETL ini.
                </div>
            @else

                <div class="etl-pipeline">

                    @foreach ($steps as $detail)
                        @php
                            $detailStatusLabel = match ($detail->status) {
                                'success' => 'Berhasil',
                                'failed' => 'Gagal',
                                'processing' => 'Diproses',
                                'rolled_back' => 'Rollback',
                                'skipped' => 'Dilewati',
                                default => ucfirst(
                                    (string) $detail->status
                                ),
                            };

                            $detailStatusClass = match ($detail->status) {
                                'success' => 'etl-step-success',
                                'failed' => 'etl-step-failed',
                                'processing' => 'etl-step-processing',
                                'rolled_back' => 'etl-step-rollback',
                                'skipped' => 'etl-step-skipped',
                                default => 'etl-step-default',
                            };

                            $typeLabel =
                                $detail->step_type === 'dimension'
                                    ? 'Dimensi'
                                    : (
                                        $detail->step_type === 'fact'
                                            ? 'Fakta'
                                            : ucfirst(
                                                (string) $detail->step_type
                                            )
                                    );

                            $typeClass =
                                $detail->step_type === 'dimension'
                                    ? 'etl-type-dimension'
                                    : 'etl-type-fact';

                            $detailSource =
                                (int) ($detail->source_rows ?? 0);

                            $detailTarget =
                                (int) ($detail->target_rows ?? 0);

                            $difference =
                                $detailTarget - $detailSource;
                        @endphp

                        <article class="etl-step">

                            {{-- STEP NUMBER --}}
                            <div>
                                <span class="etl-step-number">
                                    {{ $detail->step_order }}
                                </span>
                            </div>

                            {{-- STEP NAME --}}
                            <div>
                                <div class="etl-step-title">
                                    {{ $detail->step_name }}
                                </div>

                                <span class="etl-step-key">
                                    {{ $detail->step_key }}
                                </span>

                                <div class="etl-step-badges">

                                    <span
                                        class="etl-type {{ $typeClass }}"
                                    >
                                        {{ $typeLabel }}
                                    </span>

                                    <span
                                        class="
                                            etl-step-status
                                            {{ $detailStatusClass }}
                                        "
                                    >
                                        {{ $detailStatusLabel }}
                                    </span>

                                </div>

                                @if ($detail->rolled_back)
                                    <span class="etl-rollback-note">
                                        ↶ Transaction Rollback
                                    </span>
                                @endif

                                @if ($detail->error_message)
                                    <div class="etl-step-error">
                                        {{ $detail->error_message }}
                                    </div>
                                @endif
                            </div>

                            {{-- DIFFERENCE --}}
                            <div class="etl-difference">
                                <div class="etl-difference-label">
                                    Selisih
                                </div>

                                <div
                                    class="
                                        etl-difference-value
                                        {{ $difference === 0
                                            ? 'etl-positive'
                                            : 'etl-negative' }}
                                    "
                                >
                                    @if ($difference > 0)
                                        +
                                    @endif

                                    {{ number_format(
                                        $difference,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </div>
                            </div>

                            {{-- SOURCE → TARGET --}}
                            <div class="etl-row-flow">
                                <div class="etl-row-box">
                                    <span class="etl-row-label">
                                        Source
                                    </span>

                                    <span class="etl-row-value">
                                        {{ number_format(
                                            $detailSource,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </span>
                                </div>

                                <span class="etl-flow-arrow">
                                    →
                                </span>

                                <div class="etl-row-box">
                                    <span class="etl-row-label">
                                        Target
                                    </span>

                                    <span class="etl-row-value">
                                        {{ number_format(
                                            $detailTarget,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </span>
                                </div>
                            </div>

                            {{-- DURATION --}}
                            <div>
                                <div class="etl-duration-label">
                                    Durasi
                                </div>

                                <div class="etl-duration-value">
                                    ◷

                                    @if ($detail->duration_ms !== null)
                                        {{ number_format(
                                            $detail->duration_ms / 1000,
                                            3,
                                            ',',
                                            '.'
                                        ) }}
                                        detik
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>

                            {{-- TIME --}}
                            <div class="etl-time">

                                <div class="etl-time-row">
                                    <span class="etl-time-label">
                                        Mulai
                                    </span>

                                    <span class="etl-time-value">
                                        {{ $detail->started_at?->format(
                                            'H:i:s'
                                        ) ?? '-' }}
                                    </span>
                                </div>

                                <div class="etl-time-row">
                                    <span class="etl-time-label">
                                        Selesai
                                    </span>

                                    <span class="etl-time-value">
                                        {{ $detail->finished_at?->format(
                                            'H:i:s'
                                        ) ?? '-' }}
                                    </span>
                                </div>

                                @if ($detail->started_at)
                                    <div
                                        style="
                                            margin-top: 2px;
                                            color: #94a3b8;
                                            font-size: 8px;
                                        "
                                    >
                                        {{ $detail->started_at->format(
                                            'd M Y'
                                        ) }}
                                    </div>
                                @endif

                            </div>

                        </article>
                    @endforeach

                </div>

            @endif

            <div class="etl-footer-note">
                <span class="etl-footer-icon">
                    i
                </span>

                <span>
                    Setiap tahap menampilkan jumlah record sumber dan
                    target untuk membantu memonitor kesesuaian proses
                    ETL dari database operasional menuju Data Warehouse.
                </span>
            </div>
        </section>

    </div>
</x-filament-panels::page>