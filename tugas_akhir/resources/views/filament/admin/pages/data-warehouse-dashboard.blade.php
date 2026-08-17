<x-filament-panels::page>
    <style>
        /* =========================================================
         * DATA WAREHOUSE ANALYTICS DASHBOARD
         * ======================================================= */

        .dw-page {
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            padding: 16px 12px 52px;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        /* =========================================================
         * HERO
         * ======================================================= */

        .dw-hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            border-radius: 28px;
            border: 1px solid rgba(148, 163, 184, .24);
            background:
                radial-gradient(circle at 0% 0%, rgba(59, 130, 246, .22), transparent 35%),
                radial-gradient(circle at 100% 100%, rgba(16, 185, 129, .18), transparent 34%),
                linear-gradient(135deg, #ffffff 0%, #f8fbff 48%, #f5fdf9 100%);
            box-shadow:
                0 24px 55px rgba(15, 23, 42, .08),
                inset 0 1px 0 rgba(255, 255, 255, .85);
        }

        .dw-hero::before,
        .dw-hero::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            filter: blur(2px);
            pointer-events: none;
            z-index: -1;
        }

        .dw-hero::before {
            width: 260px;
            height: 260px;
            top: -150px;
            right: 220px;
            background: rgba(37, 99, 235, .07);
        }

        .dw-hero::after {
            width: 220px;
            height: 220px;
            bottom: -140px;
            left: 38%;
            background: rgba(5, 150, 105, .08);
        }

        .dw-hero-inner {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 240px;
            gap: 34px;
            align-items: center;
            padding: 30px;
        }

        .dw-badge,
        .dw-section-kicker {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            border: 1px solid #dbeafe;
            background: linear-gradient(135deg, #eff6ff, #f8fbff);
            color: #1d4ed8;
            padding: 7px 12px;
            font-size: 11px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: .055em;
            text-transform: uppercase;
        }

        .dw-title {
            margin: 16px 0 0;
            max-width: 780px;
            color: #0f172a;
            font-size: clamp(28px, 3vw, 38px);
            line-height: 1.12;
            letter-spacing: -.035em;
            font-weight: 950;
        }

        .dw-description {
            max-width: 800px;
            margin: 13px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.75;
        }

        .dw-description strong {
            color: #334155;
            font-weight: 900;
        }

        .dw-meta {
            margin-top: 22px;
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .dw-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 34px;
            padding: 8px 11px;
            border-radius: 11px;
            border: 1px solid rgba(226, 232, 240, .94);
            background: rgba(255, 255, 255, .78);
            box-shadow: 0 5px 15px rgba(15, 23, 42, .035);
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            backdrop-filter: blur(6px);
        }

        .dw-actions {
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .dw-btn {
            width: 100%;
            min-height: 47px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 15px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            transition:
                transform .18s ease,
                box-shadow .18s ease,
                border-color .18s ease,
                opacity .18s ease;
        }

        .dw-btn:hover {
            transform: translateY(-2px);
        }

        .dw-btn:disabled {
            cursor: wait;
            opacity: .65;
            transform: none;
        }

        .dw-btn-primary {
            color: #ffffff;
            border: 1px solid #2563eb;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 12px 26px rgba(37, 99, 235, .26);
        }

        .dw-btn-secondary {
            color: #334155;
            border: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, .9);
            box-shadow: 0 8px 18px rgba(15, 23, 42, .05);
        }

        .dw-btn-report {
            color: #ffffff;
            border: 1px solid #0d9488;
            background: linear-gradient(135deg, #0d9488, #059669);
            box-shadow: 0 10px 24px rgba(5, 150, 105, .22);
        }

        /* =========================================================
         * SECTION
         * ======================================================= */

        .dw-section-block {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 17px;
            padding: 2px 0;
        }

        .dw-section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            padding: 0 3px;
        }

        .dw-section-heading-main {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 7px;
        }

        .dw-section-title {
            margin: 3px 0 0;
            color: #0f172a;
            font-size: 21px;
            line-height: 1.25;
            letter-spacing: -.02em;
            font-weight: 950;
        }

        .dw-section-subtitle,
        .dw-section-caption {
            max-width: 900px;
            margin: 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.65;
        }

        /* =========================================================
         * FILAMENT WIDGET WRAPPER
         * ======================================================= */

        .dw-widget-wrap {
            position: relative;
            width: 100%;
            border-radius: 22px;
        }

        .dw-widget-wrap > * {
            width: 100%;
        }

        /* =========================================================
         * ETL HISTORY
         * ======================================================= */

        .dw-history-card {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            background: #ffffff;
            box-shadow:
                0 14px 34px rgba(15, 23, 42, .055),
                0 1px 2px rgba(15, 23, 42, .02);
        }

        .dw-history-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 19px 22px;
            border-bottom: 1px solid #e2e8f0;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .07), transparent 35%),
                linear-gradient(180deg, #ffffff, #fbfdff);
        }

        .dw-history-title {
            margin: 0;
            color: #0f172a;
            font-size: 15px;
            font-weight: 950;
        }

        .dw-history-subtitle {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .dw-history-summary {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .dw-history-stat {
            min-width: 82px;
            padding: 8px 11px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            text-align: center;
        }

        .dw-history-stat-value {
            display: block;
            color: #0f172a;
            font-size: 15px;
            font-weight: 950;
            line-height: 1;
        }

        .dw-history-stat-label {
            display: block;
            margin-top: 4px;
            color: #94a3b8;
            font-size: 9px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .dw-history-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .dw-history-table {
            width: 100%;
            min-width: 1080px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .dw-history-table th {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            font-size: 10px;
            line-height: 1.3;
            font-weight: 950;
            letter-spacing: .055em;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dw-history-table th.dw-number,
        .dw-history-table td.dw-number {
            text-align: right;
        }

        .dw-history-table td {
            padding: 14px 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            font-size: 12px;
            line-height: 1.45;
            vertical-align: middle;
            transition: background .16s ease;
        }

        .dw-history-table tbody tr:hover td {
            background: #f8fbff;
        }

        .dw-history-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .dw-batch-code {
            display: inline-flex;
            max-width: 215px;
            padding: 7px 9px;
            border-radius: 9px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            font-family:
                ui-monospace,
                SFMono-Regular,
                Menlo,
                Monaco,
                Consolas,
                monospace;
            font-size: 11px;
            line-height: 1.35;
            font-weight: 800;
            word-break: break-all;
        }

        .dw-trigger-badge,
        .dw-status-badge,
        .dw-match-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 950;
            line-height: 1;
            white-space: nowrap;
        }

        .dw-trigger-badge,
        .dw-status-badge {
            padding: 6px 9px;
        }

        .dw-trigger-manual {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }

        .dw-trigger-scheduler {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .dw-status-badge::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: currentColor;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, .55);
        }

        .dw-status-success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .dw-status-failed {
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        .dw-status-running {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .dw-status-default {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .dw-executor {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 140px;
        }

        .dw-executor-avatar {
            flex: 0 0 auto;
            width: 31px;
            height: 31px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 950;
        }

        .dw-executor-name {
            display: block;
            color: #334155;
            font-size: 12px;
            font-weight: 850;
        }

        .dw-executor-type {
            display: block;
            margin-top: 2px;
            color: #94a3b8;
            font-size: 9px;
            font-weight: 800;
        }

        .dw-row-number {
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
        }

        .dw-match-badge {
            margin-top: 5px;
            padding: 4px 7px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #d1fae5;
            font-size: 8px;
            letter-spacing: .025em;
            text-transform: uppercase;
        }

        .dw-duration {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #334155;
            font-weight: 850;
            white-space: nowrap;
        }

        .dw-duration::before {
            content: "◷";
            color: #94a3b8;
            font-size: 14px;
        }

        .dw-date-primary {
            display: block;
            color: #334155;
            font-size: 11px;
            font-weight: 850;
            white-space: nowrap;
        }

        .dw-date-secondary {
            display: block;
            margin-top: 3px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 750;
            white-space: nowrap;
        }

        /* =========================================================
         * FILTER
         * ======================================================= */

        .dw-filter-card {
            position: relative;
            overflow: hidden;
            padding: 24px;
            border: 1px solid #e2e8f0;
            border-radius: 25px;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .055), transparent 28%),
                #ffffff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .055);
        }

        .dw-filter-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .dw-active-filter {
            max-width: 50%;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            justify-content: flex-end;
        }

        .dw-chip {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 6px 10px;
            border-radius: 9px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 10px;
            line-height: 1;
            font-weight: 900;
        }

        .dw-filter-grid-calendar {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
        }

        .dw-field {
            position: relative;
            padding: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #f8fafc;
            transition:
                border-color .18s ease,
                background .18s ease,
                transform .18s ease;
        }

        .dw-field:focus-within {
            border-color: #bfdbfe;
            background: #ffffff;
        }

        .dw-label {
            display: block;
            margin: 0 0 8px;
            color: #475569;
            font-size: 10px;
            line-height: 1;
            font-weight: 950;
            letter-spacing: .035em;
            text-transform: uppercase;
        }

        .dw-select,
        .dw-input {
            width: 100%;
            height: 43px;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            background-color: #ffffff;
            color: #0f172a;
            font-size: 12px;
            font-weight: 750;
            outline: none;
            transition:
                border-color .18s ease,
                box-shadow .18s ease;
        }

        .dw-select {
            appearance: none;
            padding: 0 38px 0 12px;
            background-image:
                linear-gradient(45deg, transparent 50%, #64748b 50%),
                linear-gradient(135deg, #64748b 50%, transparent 50%);
            background-position:
                calc(100% - 18px) 18px,
                calc(100% - 13px) 18px;
            background-size: 5px 5px, 5px 5px;
            background-repeat: no-repeat;
        }

        .dw-input {
            padding: 0 12px;
        }

        .dw-select:focus,
        .dw-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .11);
        }

        .dw-filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 14px;
        }

        .dw-small-btn,
        .dw-focus-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 900;
            cursor: pointer;
            transition:
                transform .16s ease,
                background .16s ease;
        }

        .dw-small-btn {
            min-height: 36px;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 10px;
        }

        .dw-small-btn:hover,
        .dw-focus-btn:hover {
            background: #dbeafe;
            transform: translateY(-1px);
        }

        /* =========================================================
         * KPI
         * ======================================================= */

        .dw-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .dw-kpi-card {
            position: relative;
            overflow: hidden;
            min-height: 145px;
            padding: 18px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .045);
            transition:
                transform .18s ease,
                box-shadow .18s ease;
        }

        .dw-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 32px rgba(15, 23, 42, .07);
        }

        .dw-kpi-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 18px;
            right: 18px;
            height: 3px;
            border-radius: 0 0 999px 999px;
            background: linear-gradient(90deg, #2563eb, #60a5fa);
        }

        .dw-kpi-card:nth-child(2)::before {
            background: linear-gradient(90deg, #059669, #34d399);
        }

        .dw-kpi-card:nth-child(3)::before {
            background: linear-gradient(90deg, #7c3aed, #a78bfa);
        }

        .dw-kpi-card:nth-child(4)::before {
            background: linear-gradient(90deg, #ea580c, #fb923c);
        }

        .dw-kpi-label {
            color: #64748b;
            font-size: 9px;
            line-height: 1.3;
            font-weight: 950;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .dw-kpi-value {
            margin-top: 11px;
            color: #0f172a;
            font-size: 25px;
            line-height: 1.1;
            letter-spacing: -.025em;
            font-weight: 950;
        }

        .dw-kpi-note {
            margin-top: 9px;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.55;
        }

        .dw-positive {
            color: #059669 !important;
        }

        .dw-negative {
            color: #dc2626 !important;
        }

        .dw-neutral {
            color: #475569 !important;
        }

        .dw-info {
            color: #2563eb !important;
        }

        /* =========================================================
         * INSIGHT
         * ======================================================= */

        .dw-insight-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .dw-insight-card {
            display: grid;
            grid-template-columns: 43px minmax(0, 1fr);
            gap: 13px;
            align-items: start;
            padding: 17px;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
        }

        .dw-insight-icon {
            width: 43px;
            height: 43px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 17px;
            font-weight: 950;
        }

        .dw-insight-card[data-tone="positive"] .dw-insight-icon {
            background: #ecfdf5;
            color: #047857;
        }

        .dw-insight-card[data-tone="warning"] .dw-insight-icon {
            background: #fff7ed;
            color: #c2410c;
        }

        .dw-insight-card[data-tone="danger"] .dw-insight-icon {
            background: #fff1f2;
            color: #be123c;
        }

        .dw-insight-title {
            margin: 1px 0 0;
            color: #0f172a;
            font-size: 13px;
            font-weight: 950;
        }

        .dw-insight-body {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 11px;
            line-height: 1.6;
        }

        /* =========================================================
         * STANDARD TABLE CARD
         * ======================================================= */

        .dw-table-card {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 11px 28px rgba(15, 23, 42, .045);
        }

        .dw-table-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 17px 19px;
            border-bottom: 1px solid #e2e8f0;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .045), transparent 35%),
                #fbfdff;
        }

        .dw-table-title {
            margin: 0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 950;
        }

        .dw-table-description {
            margin: 5px 0 0;
            color: #94a3b8;
            font-size: 10px;
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
            padding: 11px 14px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            font-size: 9px;
            line-height: 1.4;
            font-weight: 950;
            letter-spacing: .055em;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dw-table td {
            padding: 13px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            font-size: 11px;
            vertical-align: middle;
            transition: background .15s ease;
        }

        .dw-table tbody tr:hover td {
            background: #fafcff;
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
            color: #1e293b;
            font-weight: 900;
        }

        .dw-muted {
            color: #94a3b8;
            font-size: 9px;
        }

        .dw-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 8px;
            font-size: 9px;
            line-height: 1;
            font-weight: 950;
            white-space: nowrap;
        }

        .dw-status-fast,
        .dw-status-aman {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #d1fae5;
        }

        .dw-status-slow,
        .dw-status-menipis {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }

        .dw-status-non_moving,
        .dw-status-habis {
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        .dw-focus-btn {
            min-height: 30px;
            padding: 6px 9px;
            border-radius: 8px;
            font-size: 9px;
            white-space: nowrap;
        }

        .dw-empty-state {
            padding: 30px 20px;
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.65;
            text-align: center;
        }

        /* =========================================================
         * CHARTS
         * ======================================================= */

        .dw-chart-filter-grid {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 14px;
            align-items: stretch;
        }

        .dw-chart-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        /* =========================================================
         * SOURCE DATA
         * ======================================================= */

        .dw-source-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 13px;
        }

        .dw-source-list {
            margin: 0;
            color: #64748b;
            font-size: 11px;
            line-height: 1.75;
            word-break: break-word;
        }

        /* =========================================================
         * DARK MODE
         * ======================================================= */

        .dark .dw-hero {
            border-color: rgba(51, 65, 85, .9);
            background:
                radial-gradient(circle at 0% 0%, rgba(37, 99, 235, .24), transparent 34%),
                radial-gradient(circle at 100% 100%, rgba(5, 150, 105, .17), transparent 34%),
                linear-gradient(135deg, #0f172a, #111827);
            box-shadow: 0 24px 55px rgba(0, 0, 0, .18);
        }

        .dark .dw-title,
        .dark .dw-section-title,
        .dark .dw-history-title,
        .dark .dw-history-stat-value,
        .dark .dw-table-title,
        .dark .dw-kpi-value,
        .dark .dw-insight-title,
        .dark .dw-name,
        .dark .dw-row-number {
            color: #f8fafc;
        }

        .dark .dw-description strong {
            color: #e2e8f0;
        }

        .dark .dw-description,
        .dark .dw-section-subtitle,
        .dark .dw-section-caption,
        .dark .dw-history-subtitle,
        .dark .dw-source-list {
            color: #94a3b8;
        }

        .dark .dw-meta-item {
            border-color: #334155;
            background: rgba(15, 23, 42, .7);
            color: #cbd5e1;
        }

        .dark .dw-badge,
        .dark .dw-section-kicker,
        .dark .dw-chip {
            border-color: rgba(59, 130, 246, .35);
            background: rgba(37, 99, 235, .15);
            color: #bfdbfe;
        }

        .dark .dw-btn-secondary {
            border-color: #334155;
            background: #0f172a;
            color: #e2e8f0;
        }

        .dark .dw-history-card,
        .dark .dw-filter-card,
        .dark .dw-table-card,
        .dark .dw-kpi-card,
        .dark .dw-insight-card {
            border-color: #334155;
            background: #111827;
            box-shadow: 0 15px 32px rgba(0, 0, 0, .15);
        }

        .dark .dw-history-header,
        .dark .dw-table-header {
            border-color: #334155;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .08), transparent 35%),
                #0f172a;
        }

        .dark .dw-history-stat {
            border-color: #334155;
            background: #111827;
        }

        .dark .dw-history-table th,
        .dark .dw-table th {
            border-color: #334155;
            background: #0f172a;
            color: #94a3b8;
        }

        .dark .dw-history-table td,
        .dark .dw-table td {
            border-color: #1e293b;
            color: #cbd5e1;
        }

        .dark .dw-history-table tbody tr:hover td,
        .dark .dw-table tbody tr:hover td {
            background: rgba(30, 41, 59, .55);
        }

        .dark .dw-batch-code {
            border-color: #334155;
            background: #0f172a;
            color: #e2e8f0;
        }

        .dark .dw-executor-avatar {
            background: rgba(37, 99, 235, .18);
            color: #bfdbfe;
        }

        .dark .dw-executor-name,
        .dark .dw-duration,
        .dark .dw-date-primary {
            color: #e2e8f0;
        }

        .dark .dw-field {
            border-color: #334155;
            background: #0f172a;
        }

        .dark .dw-field:focus-within {
            border-color: #3b82f6;
            background: #111827;
        }

        .dark .dw-label {
            color: #cbd5e1;
        }

        .dark .dw-select,
        .dark .dw-input {
            border-color: #475569;
            background-color: #111827;
            color: #f8fafc;
        }

        .dark .dw-small-btn,
        .dark .dw-focus-btn {
            border-color: rgba(59, 130, 246, .4);
            background: rgba(37, 99, 235, .15);
            color: #bfdbfe;
        }

        .dark .dw-small-btn:hover,
        .dark .dw-focus-btn:hover {
            background: rgba(37, 99, 235, .25);
        }

        .dark .dw-kpi-label,
        .dark .dw-kpi-note,
        .dark .dw-insight-body,
        .dark .dw-table-description,
        .dark .dw-empty-state {
            color: #94a3b8;
        }

        /* =========================================================
         * RESPONSIVE
         * ======================================================= */

        @media (max-width: 1200px) {
            .dw-kpi-grid,
            .dw-source-grid {
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
                min-width: 190px;
            }

            .dw-filter-grid-calendar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dw-chart-filter-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dw-page {
                gap: 26px;
                padding-left: 4px;
                padding-right: 4px;
            }

            .dw-hero {
                border-radius: 22px;
            }

            .dw-hero-inner {
                padding: 21px;
            }

            .dw-title {
                font-size: 27px;
            }

            .dw-section-heading,
            .dw-filter-header,
            .dw-history-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .dw-active-filter,
            .dw-history-summary {
                max-width: 100%;
                justify-content: flex-start;
            }

            .dw-filter-grid-calendar,
            .dw-kpi-grid,
            .dw-insight-grid,
            .dw-source-grid {
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

            .dw-filter-card {
                padding: 18px;
                border-radius: 20px;
            }
        }

        /* =========================================================
         * PREMIUM DASHBOARD OVERRIDES
         * Menyamakan visual dashboard utama dengan Detail ETL.
         * Tidak mengubah logic Livewire / filter / ETL.
         * ======================================================= */

        .dw-page {
            max-width: 1500px;
            gap: 36px;
            padding-top: 12px;
        }

        .dw-hero {
            border-radius: 32px;
            border-color: rgba(148, 163, 184, .22);
            background:
                radial-gradient(circle at 0% 0%, rgba(37, 99, 235, .25), transparent 33%),
                radial-gradient(circle at 100% 100%, rgba(5, 150, 105, .18), transparent 35%),
                radial-gradient(circle at 78% 18%, rgba(124, 58, 237, .10), transparent 24%),
                linear-gradient(135deg, #ffffff 0%, #f8fbff 50%, #f3fbf8 100%);
            box-shadow:
                0 30px 70px rgba(15, 23, 42, .10),
                0 1px 2px rgba(15, 23, 42, .03),
                inset 0 1px 0 rgba(255, 255, 255, .9);
        }

        .dw-hero::before {
            width: 360px;
            height: 360px;
            top: -235px;
            right: 14%;
            background: rgba(79, 70, 229, .09);
        }

        .dw-hero::after {
            width: 300px;
            height: 300px;
            left: auto;
            right: -150px;
            bottom: -190px;
            background: rgba(5, 150, 105, .10);
        }

        .dw-hero-inner {
            grid-template-columns: minmax(0, 1fr) 285px;
            gap: 42px;
            min-height: 310px;
            padding: 36px;
        }

        .dw-badge,
        .dw-section-kicker {
            box-shadow: 0 5px 15px rgba(37, 99, 235, .06);
        }

        .dw-title {
            max-width: 860px;
            font-size: clamp(32px, 3.2vw, 43px);
            letter-spacing: -.045em;
        }

        .dw-description {
            max-width: 850px;
            font-size: 13px;
            line-height: 1.8;
        }

        .dw-meta {
            gap: 8px;
            margin-top: 24px;
        }

        .dw-meta-item {
            min-height: 36px;
            padding: 8px 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .82);
            box-shadow:
                0 7px 20px rgba(15, 23, 42, .035),
                inset 0 1px 0 rgba(255, 255, 255, .9);
        }

        .dw-actions {
            padding: 14px;
            gap: 10px;
            border: 1px solid rgba(255, 255, 255, .90);
            border-radius: 21px;
            background: rgba(255, 255, 255, .68);
            backdrop-filter: blur(14px);
            box-shadow:
                0 18px 40px rgba(15, 23, 42, .07),
                inset 0 1px 0 rgba(255, 255, 255, .9);
        }

        .dw-btn {
            min-height: 48px;
            border-radius: 13px;
        }

        .dw-btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            box-shadow: 0 13px 28px rgba(37, 99, 235, .25);
        }

        .dw-btn-report {
            background: linear-gradient(135deg, #0d9488 0%, #059669 100%);
        }

        /* ---------- Executive strip ---------- */

        .dw-executive-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: -13px;
            position: relative;
            z-index: 3;
        }

        .dw-exec-card {
            position: relative;
            overflow: hidden;
            min-height: 132px;
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr);
            gap: 13px;
            align-items: start;
            padding: 17px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: rgba(255, 255, 255, .94);
            box-shadow:
                0 14px 32px rgba(15, 23, 42, .055),
                inset 0 1px 0 rgba(255, 255, 255, .95);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .dw-exec-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(15, 23, 42, .085);
        }

        .dw-exec-card::after {
            content: "";
            position: absolute;
            width: 90px;
            height: 90px;
            right: -38px;
            bottom: -46px;
            border-radius: 999px;
            background: rgba(37, 99, 235, .035);
        }

        .dw-exec-icon {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            border: 1px solid #dbeafe;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1d4ed8;
            font-size: 18px;
            font-weight: 950;
        }

        .dw-exec-card[data-tone="success"] .dw-exec-icon {
            border-color: #a7f3d0;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            color: #047857;
        }

        .dw-exec-card[data-tone="warning"] .dw-exec-icon {
            border-color: #fde68a;
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            color: #b45309;
        }

        .dw-exec-card[data-tone="danger"] .dw-exec-icon {
            border-color: #fecdd3;
            background: linear-gradient(135deg, #fff1f2, #ffe4e6);
            color: #be123c;
        }

        .dw-exec-label {
            color: #94a3b8;
            font-size: 8px;
            font-weight: 950;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .dw-exec-value {
            margin-top: 7px;
            color: #0f172a;
            font-size: 20px;
            line-height: 1.05;
            letter-spacing: -.025em;
            font-weight: 950;
        }

        .dw-exec-note {
            margin-top: 7px;
            color: #94a3b8;
            font-size: 9px;
            line-height: 1.5;
        }

        /* ---------- Main sections ---------- */

        .dw-section-block {
            overflow: hidden;
            gap: 19px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .035), transparent 28%),
                linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(251, 253, 255, .98));
            box-shadow:
                0 15px 36px rgba(15, 23, 42, .05),
                inset 0 1px 0 rgba(255, 255, 255, .9);
        }

        .dw-section-block::before {
            content: "";
            position: absolute;
            top: 0;
            left: 28px;
            right: 28px;
            height: 3px;
            border-radius: 0 0 999px 999px;
            background: linear-gradient(90deg, #2563eb, #4f46e5, #10b981);
            opacity: .78;
        }

        .dw-section-heading {
            padding: 2px 1px 3px;
        }

        .dw-section-title {
            font-size: 22px;
            letter-spacing: -.025em;
        }

        .dw-section-caption,
        .dw-section-subtitle {
            max-width: 980px;
        }

        .dw-widget-wrap {
            border-radius: 20px;
        }

        /* ---------- History ---------- */

        .dw-history-card {
            border-radius: 21px;
            box-shadow:
                0 10px 24px rgba(15, 23, 42, .035),
                inset 0 1px 0 rgba(255, 255, 255, .8);
        }

        .dw-history-header {
            padding: 20px 21px;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .09), transparent 31%),
                radial-gradient(circle at bottom left, rgba(5, 150, 105, .045), transparent 28%),
                linear-gradient(180deg, #ffffff, #fbfdff);
        }

        .dw-history-title {
            font-size: 16px;
        }

        .dw-history-stat {
            min-width: 88px;
            padding: 10px 12px;
            border-radius: 13px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, .035);
        }

        .dw-history-table {
            min-width: 1160px;
        }

        .dw-history-table th {
            padding-top: 13px;
            padding-bottom: 13px;
            background: #f8fafc;
        }

        .dw-history-table td {
            padding-top: 15px;
            padding-bottom: 15px;
        }

        .dw-history-table tbody tr:nth-child(even) td {
            background: rgba(248, 250, 252, .44);
        }

        .dw-history-table tbody tr:hover td {
            background: #eff6ff;
        }

        .dw-batch-code {
            border-radius: 10px;
            background: linear-gradient(135deg, #f8fafc, #ffffff);
        }

        .dw-focus-btn {
            min-height: 32px;
            border-radius: 9px;
            padding: 7px 10px;
            text-decoration: none;
            box-shadow: 0 5px 13px rgba(37, 99, 235, .055);
        }

        /* ---------- Filter ---------- */

        .dw-filter-card {
            padding: 25px;
            border-radius: 28px;
            background:
                radial-gradient(circle at 96% 3%, rgba(79, 70, 229, .075), transparent 26%),
                radial-gradient(circle at 3% 100%, rgba(5, 150, 105, .04), transparent 25%),
                #ffffff;
            box-shadow:
                0 17px 40px rgba(15, 23, 42, .055),
                inset 0 1px 0 rgba(255, 255, 255, .9);
        }

        .dw-filter-grid-calendar {
            gap: 14px;
        }

        .dw-field {
            border-radius: 17px;
            background: linear-gradient(180deg, #f8fafc, #fbfdff);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .8);
        }

        .dw-field:hover {
            border-color: #cbd5e1;
        }

        .dw-field:focus-within {
            transform: translateY(-1px);
            box-shadow:
                0 8px 18px rgba(37, 99, 235, .055),
                inset 0 1px 0 rgba(255, 255, 255, .9);
        }

        .dw-chip {
            border-radius: 999px;
            padding-left: 11px;
            padding-right: 11px;
        }

        /* ---------- Decision KPI ---------- */

        .dw-kpi-card {
            min-height: 152px;
            border-radius: 21px;
            box-shadow:
                0 11px 27px rgba(15, 23, 42, .04),
                inset 0 1px 0 rgba(255, 255, 255, .9);
        }

        .dw-kpi-value {
            font-size: 27px;
        }

        .dw-insight-card {
            min-height: 116px;
            border-radius: 20px;
            box-shadow:
                0 9px 24px rgba(15, 23, 42, .04),
                inset 0 1px 0 rgba(255, 255, 255, .8);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .dw-insight-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 31px rgba(15, 23, 42, .065);
        }

        /* ---------- Standard data tables ---------- */

        .dw-table-card {
            border-radius: 21px;
        }

        .dw-table-header {
            padding: 19px 20px;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .065), transparent 30%),
                linear-gradient(180deg, #ffffff, #fbfdff);
        }

        .dw-table-title {
            font-size: 15px;
        }

        .dw-table th {
            padding: 12px 15px;
        }

        .dw-table td {
            padding: 14px 15px;
        }

        .dw-table tbody tr:nth-child(even) td {
            background: rgba(248, 250, 252, .45);
        }

        .dw-table tbody tr:hover td {
            background: #eff6ff;
        }

        /* ---------- Source cards ---------- */

        .dw-source-grid .dw-field {
            min-height: 150px;
            padding: 17px;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .dw-source-grid .dw-field:hover {
            transform: translateY(-3px);
            border-color: #bfdbfe;
            box-shadow: 0 13px 27px rgba(37, 99, 235, .055);
        }

        .dw-source-grid .dw-field:nth-child(2) {
            background: linear-gradient(180deg, #faf5ff, #ffffff);
        }

        .dw-source-grid .dw-field:nth-child(3) {
            background: linear-gradient(180deg, #ecfdf5, #ffffff);
        }

        .dw-source-grid .dw-field:nth-child(4) {
            background: linear-gradient(180deg, #fff7ed, #ffffff);
        }

        /* ---------- Dark premium ---------- */

        .dark .dw-actions {
            border-color: rgba(71, 85, 105, .72);
            background: rgba(15, 23, 42, .68);
        }

        .dark .dw-exec-card,
        .dark .dw-section-block {
            border-color: #334155;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .055), transparent 28%),
                #111827;
            box-shadow: 0 18px 40px rgba(0, 0, 0, .17);
        }

        .dark .dw-exec-value {
            color: #f8fafc;
        }

        .dark .dw-filter-card {
            border-color: #334155;
            background:
                radial-gradient(circle at 96% 3%, rgba(79, 70, 229, .10), transparent 26%),
                #111827;
        }

        .dark .dw-history-table tbody tr:nth-child(even) td,
        .dark .dw-table tbody tr:nth-child(even) td {
            background: rgba(15, 23, 42, .26);
        }

        .dark .dw-history-table tbody tr:hover td,
        .dark .dw-table tbody tr:hover td {
            background: rgba(37, 99, 235, .12);
        }

        .dark .dw-source-grid .dw-field,
        .dark .dw-source-grid .dw-field:nth-child(2),
        .dark .dw-source-grid .dw-field:nth-child(3),
        .dark .dw-source-grid .dw-field:nth-child(4) {
            background: #0f172a;
        }

        @media (max-width: 1200px) {
            .dw-executive-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .dw-hero-inner {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .dw-actions {
                flex-direction: row;
                padding: 12px;
            }
        }

        @media (max-width: 768px) {
            .dw-page {
                gap: 28px;
            }

            .dw-executive-grid {
                grid-template-columns: 1fr;
                margin-top: -8px;
            }

            .dw-section-block {
                padding: 19px;
                border-radius: 22px;
            }

            .dw-section-block::before {
                left: 20px;
                right: 20px;
            }

            .dw-actions {
                flex-direction: column;
            }
        }

    </style>

    <div class="dw-page">
        @php
            $advancedAnalytics = $this->getAdvancedAnalytics();

            $etlHistory = $this->getEtlHistory();
            $latestEtl = $etlHistory->first();

            $etlSuccessCount = $etlHistory
                ->where('status', 'success')
                ->count();
        @endphp

        {{-- =====================================================
            HERO
        ====================================================== --}}
        <section class="dw-hero">
            <div class="dw-hero-inner">
                <div>
                    <span class="dw-badge">
                        📊 Data Warehouse Analytics
                    </span>

                    <h2 class="dw-title">
                        Analitik Inventori Data Warehouse
                    </h2>

                    <p class="dw-description">
                        Pantau dan analisis pergerakan inventori menggunakan data historis
                        yang telah melalui proses <strong>ETL</strong> ke tabel
                        <strong>dw_*</strong>. Dashboard ini menjadi lapisan analitik
                        terpisah dari database operasional.
                    </p>

                    <div class="dw-meta">
                        <span class="dw-meta-item">
                            ⚙ Auto sync 5 menit
                        </span>

                        <span class="dw-meta-item">
                            🗓 {{ $this->getPeriodLabel() }}
                        </span>

                        <span class="dw-meta-item">
                            🏭 {{ $this->getWarehouseLabel() }}
                        </span>

                        <span class="dw-meta-item">
                            📦 {{ $this->getProductLabel() }}
                        </span>

                        <span class="dw-meta-item">
                            🏷 {{ $this->getProductCategoryLabel() }}
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
                            ↻ Sync DW Sekarang
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


        {{-- =====================================================
            EXECUTIVE SUMMARY
            Ringkasan cepat sebelum masuk ke detail analitik.
        ====================================================== --}}
        @php
            $latestEtlStatusLabel = match ($latestEtl?->status) {
                'success' => 'Berhasil',
                'failed' => 'Gagal',
                'running' => 'Diproses',
                default => '-',
            };

            $latestEtlTone = match ($latestEtl?->status) {
                'success' => 'success',
                'failed' => 'danger',
                'running' => 'warning',
                default => 'default',
            };

            $latestSourceRows = (int) ($latestEtl?->source_rows ?? 0);
            $latestTargetRows = (int) ($latestEtl?->target_rows ?? 0);

            $latestRowsMatched =
                $latestEtl
                && $latestEtl->status === 'success'
                && $latestSourceRows === $latestTargetRows;

            $latestDurationSeconds = $latestEtl?->duration_ms !== null
                ? $latestEtl->duration_ms / 1000
                : null;

            $netMovement = $advancedAnalytics['summary']['net_movement'];
        @endphp

        <section class="dw-executive-grid">
            <article class="dw-exec-card" data-tone="{{ $latestEtlTone }}">
                <span class="dw-exec-icon">
                    {{ $latestEtl?->status === 'success' ? '✓' : ($latestEtl?->status === 'failed' ? '!' : '↻') }}
                </span>

                <div>
                    <div class="dw-exec-label">
                        Status ETL Terakhir
                    </div>

                    <div
                        class="dw-exec-value
                        {{ $latestEtl?->status === 'success'
                            ? 'dw-positive'
                            : ($latestEtl?->status === 'failed' ? 'dw-negative' : 'dw-info') }}"
                    >
                        {{ $latestEtlStatusLabel }}
                    </div>

                    <div class="dw-exec-note">
                        {{ $latestEtl?->batch_code ?? 'Belum ada batch ETL.' }}
                    </div>
                </div>
            </article>

            <article class="dw-exec-card" data-tone="success">
                <span class="dw-exec-icon">
                    ≡
                </span>

                <div>
                    <div class="dw-exec-label">
                        Rekonsiliasi Batch
                    </div>

                    <div class="dw-exec-value {{ $latestRowsMatched ? 'dw-positive' : 'dw-info' }}">
                        {{ $latestRowsMatched ? 'Sesuai' : 'Periksa' }}
                    </div>

                    <div class="dw-exec-note">
                        {{ number_format($latestSourceRows, 0, ',', '.') }}
                        source →
                        {{ number_format($latestTargetRows, 0, ',', '.') }}
                        target
                    </div>
                </div>
            </article>

            <article
                class="dw-exec-card"
                data-tone="{{ $netMovement >= 0 ? 'success' : 'warning' }}"
            >
                <span class="dw-exec-icon">
                    ↕
                </span>

                <div>
                    <div class="dw-exec-label">
                        Pergerakan Bersih
                    </div>

                    <div class="dw-exec-value {{ $netMovement >= 0 ? 'dw-positive' : 'dw-negative' }}">
                        {{ $netMovement > 0 ? '+' : '' }}
                        {{ number_format($netMovement, 0, ',', '.') }}
                    </div>

                    <div class="dw-exec-note">
                        Qty masuk dikurangi qty keluar pada filter aktif.
                    </div>
                </div>
            </article>

            <article class="dw-exec-card">
                <span class="dw-exec-icon">
                    ◷
                </span>

                <div>
                    <div class="dw-exec-label">
                        Durasi ETL Terakhir
                    </div>

                    <div class="dw-exec-value">
                        @if ($latestDurationSeconds !== null)
                            {{ number_format($latestDurationSeconds, 2, ',', '.') }}
                            <span style="font-size: 11px; color: #94a3b8;">detik</span>
                        @else
                            -
                        @endif
                    </div>

                    <div class="dw-exec-note">
                        {{ $latestEtl?->created_at?->format('d M Y H:i:s') ?? 'Belum ada data.' }}
                    </div>
                </div>
            </article>
        </section>

        {{-- =====================================================
            ETL MONITORING
        ====================================================== --}}
        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        ETL Monitoring
                    </span>

                    <h3 class="dw-section-title">
                        Informasi Sinkronisasi ETL
                    </h3>

                    <p class="dw-section-caption">
                        Informasi proses sinkronisasi Data Warehouse, status ETL,
                        data terbaru, dan jumlah baris data inventori yang telah terbentuk.
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

        {{-- =====================================================
            ETL HISTORY
            IMPORTANT: PLAIN BLADE, NOT NESTED TABLE WIDGET
        ====================================================== --}}
        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        ETL History
                    </span>

                    <h3 class="dw-section-title">
                        Riwayat Proses ETL
                    </h3>

                    <p class="dw-section-caption">
                        Riwayat lima proses sinkronisasi Data Warehouse inventori terbaru,
                        lengkap dengan pemicu, pelaksana, status, jumlah baris, dan durasi.
                    </p>
                </div>
            </div>

            <div class="dw-history-card">
                <div class="dw-history-header">
                    <div>
                        <h4 class="dw-history-title">
                            Aktivitas ETL Terbaru
                        </h4>

                        <p class="dw-history-subtitle">
                            Monitoring batch sinkronisasi dari database operasional
                            menuju tabel dimensi dan fakta Data Warehouse.
                        </p>
                    </div>

                    <div class="dw-history-summary">
                        <div class="dw-history-stat">
                            <span class="dw-history-stat-value">
                                {{ $etlHistory->count() }}
                            </span>

                            <span class="dw-history-stat-label">
                                Ditampilkan
                            </span>
                        </div>

                        <div class="dw-history-stat">
                            <span class="dw-history-stat-value dw-positive">
                                {{ $etlSuccessCount }}
                            </span>

                            <span class="dw-history-stat-label">
                                Success
                            </span>
                        </div>

                        <div class="dw-history-stat">
                            <span
                                class="dw-history-stat-value
                                {{ $latestEtl?->status === 'success' ? 'dw-positive' : ($latestEtl?->status === 'failed' ? 'dw-negative' : 'dw-info') }}"
                            >
                                @if ($latestEtl)
                                    @switch($latestEtl->status)
                                        @case('success')
                                            OK
                                            @break

                                        @case('failed')
                                            Failed
                                            @break

                                        @case('running')
                                            Running
                                            @break

                                        @default
                                            {{ ucfirst((string) $latestEtl->status) }}
                                    @endswitch
                                @else
                                    -
                                @endif
                            </span>

                            <span class="dw-history-stat-label">
                                Terakhir
                            </span>
                        </div>
                    </div>
                </div>

                <div class="dw-history-scroll">
                    <table class="dw-history-table">
                        <thead>
                            <tr>
                                <th>Batch ETL</th>
                                <th>Pemicu</th>
                                <th>Dijalankan Oleh</th>
                                <th>Status</th>
                                <th class="dw-number">Baris Sumber</th>
                                <th class="dw-number">Baris Target</th>
                                <th>Durasi</th>
                                <th>Waktu Proses</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($etlHistory as $run)
                                @php
                                    $triggerLabel = $run->trigger_type === 'scheduler'
                                        ? 'Scheduler'
                                        : 'Manual';

                                    $triggerClass = $run->trigger_type === 'scheduler'
                                        ? 'dw-trigger-scheduler'
                                        : 'dw-trigger-manual';

                                    $statusLabel = match ($run->status) {
                                        'success' => 'Success',
                                        'failed' => 'Failed',
                                        'running' => 'Processing',
                                        default => ucfirst((string) $run->status),
                                    };

                                    $statusClass = match ($run->status) {
                                        'success' => 'dw-status-success',
                                        'failed' => 'dw-status-failed',
                                        'running' => 'dw-status-running',
                                        default => 'dw-status-default',
                                    };

                                    $executor = $run->trigger_type === 'scheduler'
                                        ? 'Sistem'
                                        : ($run->triggeredByUser?->name ?? 'CLI / Sistem');

                                    $executorInitial = mb_strtoupper(
                                        mb_substr($executor, 0, 1)
                                    );

                                    $sourceRows = (int) ($run->source_rows ?? 0);
                                    $targetRows = (int) ($run->target_rows ?? 0);

                                    $rowsMatched =
                                        $run->status === 'success'
                                        && $sourceRows === $targetRows;
                                @endphp

                                <tr>
                                    <td>
                                        <span class="dw-batch-code">
                                            {{ $run->batch_code ?? '-' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="dw-trigger-badge {{ $triggerClass }}">
                                            {{ $triggerLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="dw-executor">
                                            <span class="dw-executor-avatar">
                                                {{ $executorInitial }}
                                            </span>

                                            <span>
                                                <span class="dw-executor-name">
                                                    {{ $executor }}
                                                </span>

                                                <span class="dw-executor-type">
                                                    {{ $run->trigger_type === 'scheduler'
                                                        ? 'Otomatis'
                                                        : 'Eksekusi manual' }}
                                                </span>
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="dw-status-badge {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td class="dw-number">
                                        <span class="dw-row-number">
                                            {{ number_format($sourceRows, 0, ',', '.') }}
                                        </span>
                                    </td>

                                    <td class="dw-number">
                                        <span class="dw-row-number">
                                            {{ number_format($targetRows, 0, ',', '.') }}
                                        </span>

                                        @if ($rowsMatched)
                                            <div>
                                                <span class="dw-match-badge">
                                                    ✓ Sesuai
                                                </span>
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($run->duration_ms !== null)
                                            <span class="dw-duration">
                                                {{ number_format(
                                                    $run->duration_ms / 1000,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }}
                                                detik
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        @if ($run->created_at)
                                            <span class="dw-date-primary">
                                                {{ $run->created_at->format('d M Y') }}
                                            </span>

                                            <span class="dw-date-secondary">
                                                Mulai {{ $run->created_at->format('H:i:s') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <a
                                            href="{{ \App\Filament\Admin\Pages\DataWarehouseEtlDetail::getUrl([
                                                'run' => $run->id,
                                            ]) }}"
                                            class="dw-focus-btn"
                                        >
                                            Lihat Detail →
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="dw-empty-state">
                                            Belum terdapat riwayat proses ETL.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- =====================================================
            FILTER
        ====================================================== --}}
        <section class="dw-filter-card">
            <div class="dw-filter-header">
                <div>
                    <span class="dw-section-kicker">
                        Filter Analysis
                    </span>

                    <h3 class="dw-section-title">
                        Filter Analitik
                    </h3>

                    <p class="dw-section-subtitle">
                        Gunakan dimensi waktu, gudang, produk, dan kategori produk
                        untuk menganalisis data historis dari sudut pandang yang berbeda.
                    </p>
                </div>

                <div class="dw-active-filter">
                    <span class="dw-chip">
                        🗓 {{ $this->getPeriodLabel() }}
                    </span>

                    <span class="dw-chip">
                        🏭 {{ $this->getWarehouseLabel() }}
                    </span>

                    <span class="dw-chip">
                        📦 {{ $this->getProductLabel() }}
                    </span>

                    <span class="dw-chip">
                        🏷 {{ $this->getProductCategoryLabel() }}
                    </span>
                </div>
            </div>

            <div class="dw-filter-grid-calendar">
                <div class="dw-field">
                    <label class="dw-label">
                        Periode Cepat
                    </label>

                    <select
                        wire:model.change="period"
                        class="dw-select"
                    >
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

                    <select
                        wire:model.change="warehouseId"
                        class="dw-select"
                    >
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

                    <select
                        wire:model.change="productId"
                        class="dw-select"
                    >
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

                    <select
                        wire:model.change="productCategory"
                        class="dw-select"
                    >
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
                    🗓 Kosongkan Tanggal
                </button>
            </div>
        </section>

        {{-- =====================================================
            OVERVIEW
        ====================================================== --}}
        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        Ringkasan Data Warehouse
                    </span>

                    <h3 class="dw-section-title">
                        Ringkasan Kinerja Inventori
                    </h3>

                    <p class="dw-section-caption">
                        Ringkasan jumlah produk, gudang, transaksi, kuantitas,
                        dan kondisi stok berdasarkan hasil sinkronisasi Data Warehouse.
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
                    key(
                        'dw-overview-'
                        . $period
                        . '-'
                        . $startDate
                        . '-'
                        . $endDate
                        . '-'
                        . $warehouseId
                    )
                )
            </div>
        </section>

        {{-- =====================================================
            DECISION SUPPORT
        ====================================================== --}}
        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        Analisis Pendukung Keputusan
                    </span>

                    <h3 class="dw-section-title">
                        Perbandingan dan Insight Inventori
                    </h3>

                    <p class="dw-section-caption">
                        Menunjukkan arah pergerakan barang, perubahan terhadap periode
                        sebelumnya, serta kondisi inventori yang membutuhkan perhatian.
                    </p>
                </div>
            </div>

            <div class="dw-kpi-grid">
                <div class="dw-kpi-card">
                    <div class="dw-kpi-label">
                        Pergerakan Bersih
                    </div>

                    <div
                        class="dw-kpi-value
                        {{ $advancedAnalytics['summary']['net_movement'] >= 0
                            ? 'dw-positive'
                            : 'dw-negative' }}"
                    >
                        {{ $advancedAnalytics['summary']['net_movement'] > 0 ? '+' : '' }}
                        {{ number_format(
                            $advancedAnalytics['summary']['net_movement'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </div>

                    <div class="dw-kpi-note">
                        Qty barang masuk dikurangi qty barang keluar pada filter terpilih.
                    </div>
                </div>

                <div class="dw-kpi-card">
                    <div class="dw-kpi-label">
                        Perubahan Qty Masuk
                    </div>

                    <div class="dw-kpi-value dw-info">
                        @if ($advancedAnalytics['summary']['qty_in_change'] === null)
                            -
                        @else
                            {{ $advancedAnalytics['summary']['qty_in_change'] > 0 ? '+' : '' }}
                            {{ number_format(
                                $advancedAnalytics['summary']['qty_in_change'],
                                1,
                                ',',
                                '.'
                            ) }}%
                        @endif
                    </div>

                    <div class="dw-kpi-note">
                        {{ $advancedAnalytics['comparison_note'] }}
                    </div>
                </div>

                <div class="dw-kpi-card">
                    <div class="dw-kpi-label">
                        Perubahan Qty Keluar
                    </div>

                    <div class="dw-kpi-value dw-info">
                        @if ($advancedAnalytics['summary']['qty_out_change'] === null)
                            -
                        @else
                            {{ $advancedAnalytics['summary']['qty_out_change'] > 0 ? '+' : '' }}
                            {{ number_format(
                                $advancedAnalytics['summary']['qty_out_change'],
                                1,
                                ',',
                                '.'
                            ) }}%
                        @endif
                    </div>

                    <div class="dw-kpi-note">
                        {{ $advancedAnalytics['comparison_note'] }}
                    </div>
                </div>

                <div class="dw-kpi-card">
                    <div class="dw-kpi-label">
                        Klasifikasi Produk
                    </div>

                    <div class="dw-kpi-value">
                        {{ number_format(
                            $advancedAnalytics['classification']['counts']['fast'],
                            0,
                            ',',
                            '.'
                        ) }}
                        /
                        {{ number_format(
                            $advancedAnalytics['classification']['counts']['slow'],
                            0,
                            ',',
                            '.'
                        ) }}
                        /
                        {{ number_format(
                            $advancedAnalytics['classification']['counts']['non_moving'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </div>

                    <div class="dw-kpi-note">
                        Produk cepat / lambat / tidak bergerak berdasarkan qty keluar.
                    </div>
                </div>
            </div>

            <div class="dw-insight-grid">
                @foreach ($advancedAnalytics['insights'] as $insight)
                    <article
                        class="dw-insight-card"
                        data-tone="{{ $insight['tone'] }}"
                    >
                        <span class="dw-insight-icon">
                            {{ $insight['icon'] }}
                        </span>

                        <div>
                            <h4 class="dw-insight-title">
                                {{ $insight['title'] }}
                            </h4>

                            <p class="dw-insight-body">
                                {{ $insight['body'] }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- =====================================================
            WAREHOUSE COMPARISON
        ====================================================== --}}
        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        Perbandingan Gudang
                    </span>

                    <h3 class="dw-section-title">
                        Aktivitas Inventori per Gudang
                    </h3>

                    <p class="dw-section-caption">
                        Membandingkan qty masuk, qty keluar, pergerakan bersih,
                        dan jumlah item yang perlu diperiksa pada setiap gudang.
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
                    key(
                        'dw-warehouse-comparison-'
                        . $period
                        . '-'
                        . $startDate
                        . '-'
                        . $endDate
                        . '-'
                        . $warehouseId
                        . '-'
                        . $this->productId
                        . '-'
                        . $this->productCategory
                    )
                )
            </div>

            <div class="dw-table-card">
                <div class="dw-table-header">
                    <div>
                        <h4 class="dw-table-title">
                            Rincian Perbandingan Gudang
                        </h4>

                        <p class="dw-table-description">
                            Gunakan tombol Fokus Gudang untuk menerapkan gudang
                            tersebut sebagai filter dashboard.
                        </p>
                    </div>
                </div>

                @if ($advancedAnalytics['warehouses'] === [])
                    <div class="dw-empty-state">
                        Belum ada data gudang yang dapat dibandingkan.
                    </div>
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
                                        <td>
                                            <span class="dw-name">
                                                {{ $warehouse['name'] }}
                                            </span>
                                        </td>

                                        <td class="dw-number">
                                            {{ number_format(
                                                $warehouse['qty_in'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td class="dw-number">
                                            {{ number_format(
                                                $warehouse['qty_out'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td
                                            class="dw-number
                                            {{ $warehouse['net_movement'] >= 0
                                                ? 'dw-positive'
                                                : 'dw-negative' }}"
                                        >
                                            {{ $warehouse['net_movement'] > 0 ? '+' : '' }}

                                            {{ number_format(
                                                $warehouse['net_movement'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td class="dw-number">
                                            {{ number_format(
                                                $warehouse['attention_count'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td>
                                            <button
                                                type="button"
                                                wire:click="$set('warehouseId', '{{ $warehouse['id'] }}')"
                                                class="dw-focus-btn"
                                            >
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

        {{-- =====================================================
            PRODUCT CLASSIFICATION
        ====================================================== --}}
        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        Klasifikasi Pergerakan Produk
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
                        <h4 class="dw-table-title">
                            Sampel Produk per Klasifikasi
                        </h4>

                        <p class="dw-table-description">
                            Rata-rata qty keluar produk aktif:
                            {{ number_format(
                                $advancedAnalytics['classification']['average_active_qty_out'],
                                1,
                                ',',
                                '.'
                            ) }}.
                            Ditampilkan maksimal lima produk dari setiap klasifikasi.
                        </p>
                    </div>
                </div>

                @if ($advancedAnalytics['classification']['rows'] === [])
                    <div class="dw-empty-state">
                        Belum ada produk yang dapat diklasifikasikan.
                    </div>
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
                                        <td>
                                            <span class="dw-name">
                                                {{ $product['name'] }}
                                            </span>
                                        </td>

                                        <td>
                                            {{ $product['category'] }}
                                        </td>

                                        <td>
                                            <span
                                                class="dw-status
                                                dw-status-{{ $product['classification'] }}"
                                            >
                                                {{ $product['classification_label'] }}
                                            </span>
                                        </td>

                                        <td class="dw-number">
                                            {{ number_format(
                                                $product['total_qty_out'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td class="dw-number">
                                            {{ number_format(
                                                $product['movement_frequency'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td>
                                            {{ $product['last_movement_date'] }}
                                        </td>

                                        <td>
                                            <button
                                                type="button"
                                                wire:click="$set('productId', '{{ $product['id'] }}')"
                                                class="dw-focus-btn"
                                            >
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

        {{-- =====================================================
            STOCK ALERT
        ====================================================== --}}
        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        Peringatan Stok
                    </span>

                    <h3 class="dw-section-title">
                        Item yang Perlu Diperiksa
                    </h3>

                    <p class="dw-section-caption">
                        Menggunakan snapshot stok terakhir pada periode terpilih
                        untuk menampilkan item dengan kondisi menipis atau habis.
                    </p>
                </div>
            </div>

            <div class="dw-table-card">
                <div class="dw-table-header">
                    <div>
                        <h4 class="dw-table-title">
                            Daftar Peringatan Stok
                        </h4>

                        <p class="dw-table-description">
                            Menampilkan maksimal 20 item dengan kondisi stok
                            paling mendesak.
                        </p>
                    </div>
                </div>

                @if ($advancedAnalytics['stock_alerts'] === [])
                    <div class="dw-empty-state">
                        Tidak terdapat item berstatus menipis atau habis
                        pada snapshot terakhir dalam filter terpilih.
                    </div>
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
                                            <span class="dw-name">
                                                {{ $alert['product_name'] }}
                                            </span>

                                            <br>

                                            <span class="dw-muted">
                                                {{ $alert['category'] }}
                                            </span>
                                        </td>

                                        <td>
                                            {{ $alert['warehouse_name'] }}
                                        </td>

                                        <td class="dw-number">
                                            {{ number_format(
                                                $alert['qty_available'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td class="dw-number">
                                            {{ number_format(
                                                $alert['minimum_stock'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td class="dw-number dw-negative">
                                            {{ number_format(
                                                $alert['shortage'],
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td>
                                            <span
                                                class="dw-status
                                                dw-status-{{ $alert['status'] }}"
                                            >
                                                {{ $alert['status_label'] }}
                                            </span>
                                        </td>

                                        <td>
                                            {{ $alert['snapshot_date'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        {{-- =====================================================
            CHART ANALYSIS
        ====================================================== --}}
        <section class="dw-section-block">
            <div class="dw-section-heading">
                <div class="dw-section-heading-main">
                    <span class="dw-section-kicker">
                        Analisis Pergerakan Stok
                    </span>

                    <h3 class="dw-section-title">
                        Grafik Analitik Inventori
                    </h3>

                    <p class="dw-section-caption">
                        Visualisasi tren kuantitas barang masuk dan keluar per bulan,
                        serta produk dengan aktivitas barang keluar tertinggi.
                    </p>
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
                        key(
                            'dw-movement-trend-'
                            . $period
                            . '-'
                            . $startDate
                            . '-'
                            . $endDate
                            . '-'
                            . $warehouseId
                            . '-'
                            . $this->chartYear
                            . '-'
                            . $this->productId
                            . '-'
                            . $this->productCategory
                        )
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
                        key(
                            'dw-top-product-movement-'
                            . $period
                            . '-'
                            . $startDate
                            . '-'
                            . $endDate
                            . '-'
                            . $warehouseId
                            . '-'
                            . $this->chartYear
                            . '-'
                            . $this->productId
                            . '-'
                            . $this->productCategory
                        )
                    )
                </div>
            </div>
        </section>

        {{-- =====================================================
            DATA SOURCE
        ====================================================== --}}
        <section class="dw-filter-card">
            <div class="dw-filter-header">
                <div>
                    <span class="dw-section-kicker">
                        Data Warehouse Source
                    </span>

                    <h3 class="dw-section-title">
                        Keterangan Sumber Data
                    </h3>

                    <p class="dw-section-subtitle">
                        Seluruh informasi analitik pada dashboard berasal dari
                        tabel Data Warehouse yang telah melalui proses ETL,
                        bukan langsung dari tabel transaksi operasional.
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
                        Data operasional → proses ETL → tabel dimensi dan fakta
                        → dashboard analitik.
                    </p>
                </div>

                <div class="dw-field">
                    <label class="dw-label">
                        Fungsi Analitik
                    </label>

                    <p class="dw-source-list">
                        Analisis historis inventori, pergerakan stok,
                        kondisi stok, perbandingan gudang, dan aktivitas produk.
                    </p>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>