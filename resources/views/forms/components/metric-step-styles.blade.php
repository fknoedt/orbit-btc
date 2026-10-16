@once
<style>
    .metric-rule-grid { row-gap: 1.65rem; }
    .metric-rule-body {
        padding: 1.25rem 1.35rem 1.5rem;
    }
    .metric-rule-body.has-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        column-gap: 0.85rem;
        align-items: stretch;
    }
    .metric-rule-actions {
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
    }
    .metric-rule-actions .fi-icon-btn {
        width: 3rem;
        height: 3rem;
        margin: 0;
    }
    .metric-rule-actions .fi-icon-btn svg {
        width: 1.85rem;
        height: 1.85rem;
    }
    .metric-moves .fi-fo-field-wrp-label { white-space: nowrap; }
    .metric-hint-link {
        margin: 0;
        padding: 0;
        border: 0;
        background: none;
        color: inherit;
        font: inherit;
        text-align: left;
        cursor: pointer;
    }
    .metric-hint-link:hover { color: #111827; }
    .dark .metric-hint-link:hover { color: #f9fafb; }
    .metric-multiplier .fi-fo-field-wrp-helper-text,
    .metric-hint {
        margin: 0.2rem 0 0;
        font-size: 13px;
        line-height: 1.4;
        color: #6b7280;
    }
    .dark .metric-multiplier .fi-fo-field-wrp-helper-text,
    .dark .metric-hint { color: #9ca3af; }
    .metric-composite-note {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.65rem;
        margin: 0;
        padding: 0.8rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid #f59e0b;
        background: #fffbeb;
        color: #92400e;
        font-size: 14px;
        line-height: 1.45;
    }
    .metric-composite-note p { margin: 0; }
    .metric-composite-notice {
        font-size: 13px;
        line-height: 1.4;
        color: #78350f;
    }
    .dark .metric-composite-notice { color: #fde68a; }
    .metric-composite-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .metric-single-note {
        box-sizing: border-box;
        margin: 0 auto;
        padding: 0 1rem;
        max-width: 15rem;
        text-align: center;
        font-size: 14px;
        line-height: 1.45;
        color: #374151;
    }
    .dark .metric-single-note { color: #e5e7eb; }
    .dark .metric-composite-note {
        border-color: rgba(251, 191, 36, 0.45);
        background: rgba(251, 191, 36, 0.14);
        color: #fcd34d;
    }
    .metric-help-link {
        margin: 0;
        padding: 0.28rem 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(146, 64, 14, 0.4);
        background: rgba(255, 255, 255, 0.55);
        color: #78350f;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.3;
        cursor: pointer;
    }
    .metric-help-link:hover { background: rgba(255, 255, 255, 0.85); }
    .dark .metric-help-link {
        border-color: rgba(253, 230, 138, 0.45);
        background: rgba(0, 0, 0, 0.2);
        color: #fde68a;
    }
    .dark .metric-help-link:hover { background: rgba(0, 0, 0, 0.35); }
    .metric-formula { margin-top: -0.65rem; }
    .metric-formula > .grid { row-gap: 1.35rem; }
    .metric-formula .fi-fo-field-wrp-hint {
        justify-content: flex-start;
        width: 100%;
        margin-inline-start: 0;
        text-align: left;
    }
    .metric-formula .fi-fo-field-wrp-hint-label {
        text-align: left;
    }
    .fi-fo-wizard-header { border-bottom-width: 0 !important; }
    .fi-fo-repeater-item > .fi-fo-repeater-item-content {
        border-top-width: 0 !important;
        border-bottom-width: 0 !important;
    }
    .dir-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
        min-height: 36px;
    }
    .metric-readout { text-align: left; }
    .metric-sentence {
        margin: 0;
        font-size: 1.25rem;
        line-height: 1.65;
        font-weight: 400;
        color: #111827;
    }
    .dark .metric-sentence { color: #f9fafb; }
    .metric-em {
        font-weight: 700;
        padding: 0.04em 0.32em;
        border-radius: 0.32em;
        background: #fef3c7;
        color: #78350f;
        box-decoration-break: clone;
        -webkit-box-decoration-break: clone;
    }
    .dark .metric-em {
        background: rgba(251, 191, 36, 0.22);
        color: #fde68a;
    }
    .metric-chart-icon {
        display: inline-block;
        margin: 0 0.12em 0 0.2em;
        line-height: 1;
        vertical-align: -0.12em;
        text-decoration: none;
        color: #ea580c;
        color: rgb(var(--primary-600));
    }
    .dark .metric-chart-icon {
        color: #fb923c;
        color: rgb(var(--primary-400));
    }
    .metric-chart-icon:hover {
        color: #c2410c;
        color: rgb(var(--primary-700));
    }
    .dark .metric-chart-icon:hover {
        color: #fdba74;
        color: rgb(var(--primary-300));
    }
    .metric-chart-icon:focus-visible {
        outline: 2px solid currentColor;
        outline-offset: 2px;
        border-radius: 0.2em;
    }
    .dir-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        height: 36px;
        padding: 0 10px;
        border-radius: 9999px;
        border: 1px solid #d1d5db;
        background: transparent;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
    }
    .dark .dir-pill { border-color: rgba(156, 163, 175, 0.55); }
    .dir-pill.is-up,
    .dir-pill.is-down,
    .dir-pill.is-both { color: #4b5563; }
    .dark .dir-pill.is-up,
    .dark .dir-pill.is-down,
    .dark .dir-pill.is-both { color: #d1d5db; }
    .dir-pill.is-selected {
        border-color: transparent;
        background: #dbeafe;
    }
    .dir-pill.is-selected.is-up,
    .dark .dir-pill.is-selected.is-up { color: #047857; }
    .dir-pill.is-selected.is-down,
    .dark .dir-pill.is-selected.is-down { color: #b91c1c; }
    .dir-pill.is-selected.is-both,
    .dark .dir-pill.is-selected.is-both { color: #1d4ed8; }
</style>
@endonce
