<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* === ACU: AI Credit Usage — Metering Instrument === */
.acu {
    --acu-void:    #12102A;
    --acu-ink:     #2D2858;
    --acu-muted:   #7E7A9A;
    --acu-accent:  #4C3FD4;
    --acu-glow:    #BAB5F8;
    --acu-tint:    #F2F1FF;
    --acu-border:  #DDD9FF;
    --acu-surface: #FFFFFF;
    font-family: Lato, sans-serif;
    padding-bottom: 32px;
}

/* ── Page header ── */
.acu-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}
.acu-page-header h4 {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
    font-size: 1.25rem;
    color: var(--acu-void);
    margin: 0;
    letter-spacing: -0.3px;
}
.acu-subtitle {
    color: var(--acu-muted);
    font-size: 0.8125rem;
    margin: 3px 0 0;
}
.acu-back {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: var(--acu-muted);
    text-decoration: none;
    margin-bottom: 5px;
    transition: color 0.12s;
}
.acu-back:hover { color: var(--acu-accent); text-decoration: none; }

.acu-daterange-label {
    display: block;
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--acu-muted);
    margin-bottom: 5px;
}
.acu-daterange-wrap { min-width: 260px; }
.acu-daterange-wrap .input-group-text {
    background: #fff;
    border: 1.5px solid var(--acu-border);
    border-right: none;
    color: var(--acu-muted);
}
.acu-daterange-wrap .form-control {
    border: 1.5px solid var(--acu-border);
    border-left: none;
    font-size: 0.8125rem;
    cursor: pointer;
}
.acu-daterange-wrap .form-control:focus {
    border-color: var(--acu-accent);
    box-shadow: 0 0 0 3px rgba(76,63,212,0.1);
}

/* ── Hero meter ── */
.acu-hero {
    background: var(--acu-void);
    border-radius: 12px;
    padding: 28px 32px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 0;
}
.acu-hero::before {
    content: '';
    position: absolute;
    top: -70px; right: -70px;
    width: 280px; height: 280px;
    background: radial-gradient(circle, #4C3FD4 0%, transparent 65%);
    opacity: 0.28;
    pointer-events: none;
}
.acu-hero-left {
    flex: 0 0 260px;
    position: relative;
    z-index: 1;
}
.acu-hero-sep {
    width: 1px;
    background: rgba(186,181,248,0.14);
    align-self: stretch;
    margin: 0 32px;
    flex-shrink: 0;
}
.acu-hero-right {
    flex: 1;
    min-width: 0;
    position: relative;
    z-index: 1;
}
.acu-hero-eyebrow {
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--acu-glow);
    opacity: 0.65;
    margin-bottom: 8px;
}
.acu-hero-num {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 3.25rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
    letter-spacing: -1.5px;
    font-variant-numeric: tabular-nums;
}
.acu-hero-unit {
    font-size: 1.1rem;
    font-weight: 500;
    color: var(--acu-glow);
    opacity: 0.55;
    margin-left: 4px;
    letter-spacing: 0;
}
.acu-hero-sub {
    font-size: 0.6875rem;
    color: var(--acu-glow);
    opacity: 0.38;
    margin-top: 9px;
}

/* ── Token flow bar ── */
.acu-flow-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}
.acu-flow-stat { font-family: 'Space Grotesk', sans-serif; }
.acu-flow-stat-lbl {
    display: block;
    font-size: 0.5625rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(186,181,248,0.48);
    margin-bottom: 2px;
}
.acu-flow-stat-val {
    display: block;
    font-size: 1rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: #fff;
}
.acu-flow-stat-total .acu-flow-stat-lbl,
.acu-flow-stat-total .acu-flow-stat-val { text-align: center; }
.acu-flow-stat-total .acu-flow-stat-val {
    color: rgba(186,181,248,0.45);
    font-weight: 500;
    font-size: 0.8125rem;
}
.acu-flow-stat-out .acu-flow-stat-lbl,
.acu-flow-stat-out .acu-flow-stat-val { text-align: right; }
.acu-flow-stat-out .acu-flow-stat-val { color: var(--acu-glow); }

.acu-flow-track {
    height: 8px;
    background: rgba(186,181,248,0.1);
    border-radius: 99px;
    overflow: hidden;
    display: flex;
}
.acu-flow-in {
    background: var(--acu-accent);
    height: 100%;
    width: 50%;
    transition: width 0.9s cubic-bezier(0.4,0,0.2,1);
}
.acu-flow-out {
    background: var(--acu-glow);
    height: 100%;
    width: 50%;
    transition: width 0.9s cubic-bezier(0.4,0,0.2,1);
}
.acu-flow-legend {
    display: flex;
    gap: 14px;
    margin-top: 9px;
}
.acu-flow-legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.5625rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(186,181,248,0.4);
}
.acu-legend-dot {
    width: 7px; height: 7px;
    border-radius: 2px;
    flex-shrink: 0;
}
.acu-legend-dot-in  { background: var(--acu-accent); }
.acu-legend-dot-out { background: var(--acu-glow); }

/* ── Stat cards ── */
.acu-card {
    background: var(--acu-surface);
    border: 1.5px solid var(--acu-border);
    box-shadow: 4px 4px 0 var(--acu-glow);
    border-radius: 8px;
    padding: 20px 22px 22px;
    height: 100%;
    transition: transform 0.12s, box-shadow 0.12s;
}
.acu-card:hover {
    transform: translate(-1px, -1px);
    box-shadow: 5px 5px 0 var(--acu-glow);
}
.acu-card-lbl {
    font-size: 0.5625rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--acu-muted);
    margin-bottom: 10px;
}
.acu-card-val {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--acu-void);
    line-height: 1;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.5px;
}

/* ── Panels (chart + feature) ── */
.acu-panel {
    background: var(--acu-surface);
    border: 1.5px solid var(--acu-border);
    box-shadow: 4px 4px 0 var(--acu-glow);
    border-radius: 8px;
    height: 100%;
    overflow: hidden;
}
.acu-panel-hd {
    padding: 13px 18px;
    border-bottom: 1.5px solid var(--acu-border);
    background: var(--acu-tint);
}
.acu-panel-ttl {
    font-size: 0.5625rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--acu-muted);
    margin: 0;
}
.acu-panel-bd { padding: 16px 18px; }
.acu-chart-wrap { position: relative; height: 200px; }
.acu-chart-wrap canvas { position: absolute; inset: 0; }

/* ── Feature rows ── */
.acu-feat-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid var(--acu-tint);
}
.acu-feat-row:last-child { border-bottom: none; }
.acu-feat-name {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--acu-ink);
}
.acu-feat-meta {
    font-size: 0.6875rem;
    color: var(--acu-muted);
    margin-top: 2px;
}
.acu-feat-credit {
    font-family: 'Space Grotesk', sans-serif;
    font-variant-numeric: tabular-nums;
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--acu-accent);
    text-align: right;
    flex-shrink: 0;
    margin-left: 12px;
}
.acu-feat-credit-unit {
    font-size: 0.625rem;
    font-weight: 400;
    color: var(--acu-muted);
}

/* ── Table wrap ── */
.acu-table-wrap {
    background: var(--acu-surface);
    border: 1.5px solid var(--acu-border);
    box-shadow: 4px 4px 0 var(--acu-glow);
    border-radius: 8px;
    overflow: hidden;
}
.acu-table-hd {
    padding: 13px 18px;
    background: var(--acu-tint);
    border-bottom: 1.5px solid var(--acu-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}
.acu-table-hd-ttl {
    font-size: 0.5625rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--acu-muted);
}
.acu-search-wrap { position: relative; }
.acu-search-wrap .acu-ico {
    position: absolute;
    left: 9px; top: 50%;
    transform: translateY(-50%);
    color: var(--acu-muted);
    font-size: 14px;
    pointer-events: none;
}
.acu .acu-search-input {
    padding-left: 30px;
    border: 1.5px solid var(--acu-border);
    border-radius: 6px;
    font-size: 0.8125rem;
    color: var(--acu-ink);
    height: 34px;
    min-width: 200px;
    background: #fff;
}
.acu .acu-search-input:focus {
    border-color: var(--acu-accent);
    box-shadow: 0 0 0 3px rgba(76,63,212,0.1);
    outline: none;
}

/* ── Tables ── */
.acu .ai-usage-table,
.acu .ai-usage-responses-table {
    font-size: 0.8125rem;
    color: var(--acu-ink);
    border-collapse: collapse;
    width: 100%;
    margin: 0;
}
.acu .ai-usage-table thead th,
.acu .ai-usage-responses-table thead th {
    font-size: 0.5625rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--acu-accent);
    background: var(--acu-tint);
    border-top: none;
    border-bottom: 1.5px solid var(--acu-border) !important;
    padding: 10px 16px;
}
.acu .ai-usage-table tbody td,
.acu .ai-usage-responses-table tbody td {
    padding: 10px 16px;
    border-top: none !important;
    border-bottom: 1px solid var(--acu-tint) !important;
    vertical-align: middle;
}
.acu .ai-usage-table tbody tr:last-child td,
.acu .ai-usage-responses-table tbody tr:last-child td {
    border-bottom: none !important;
}
.acu .ai-usage-table tbody tr:hover td,
.acu .ai-usage-responses-table tbody tr:hover td {
    background: var(--acu-tint);
}
.acu .ai-usage-table td:not(:first-child),
.acu .ai-usage-responses-table td:not(:first-child) {
    font-family: 'Space Grotesk', sans-serif;
    font-variant-numeric: tabular-nums;
}
.acu .ai-usage-table td a,
.acu .ai-usage-responses-table td a {
    color: var(--acu-accent);
    font-weight: 600;
    text-decoration: none;
}
.acu .ai-usage-table td a:hover,
.acu .ai-usage-responses-table td a:hover { text-decoration: underline; }

/* ── DataTables chrome ── */
.acu .dataTables_wrapper .dataTables_paginate { padding: 12px 16px; }
.acu .dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 4px !important;
    font-size: 0.75rem;
    padding: 4px 10px !important;
}
.acu .dataTables_wrapper .dataTables_paginate .paginate_button.current,
.acu .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: var(--acu-accent) !important;
    border-color: var(--acu-accent) !important;
    color: #fff !important;
}
.acu .dataTables_info {
    padding: 12px 16px;
    font-size: 0.75rem;
    color: var(--acu-muted);
}
.acu .dataTables_processing { color: var(--acu-accent); font-size: 0.75rem; }

/* ── Responsive ── */
@media (max-width: 767px) {
    .acu-hero {
        flex-direction: column;
        align-items: flex-start;
        padding: 20px;
    }
    .acu-hero-sep { width: 100%; height: 1px; margin: 16px 0; align-self: auto; }
    .acu-hero-num { font-size: 2.25rem; }
    .acu-hero-left { flex: none; width: 100%; }
    .acu-hero-right { width: 100%; }
}
@media (prefers-reduced-motion: reduce) {
    .acu-card, .acu-flow-in, .acu-flow-out { transition: none; }
}
</style>
