@once
<style>
    .lm-editor-form { width: 100%; }
    .lm-editor-grid {
        display: grid;
        gap: 1rem;
        align-items: start;
    }
    @media (min-width: 900px) {
        .lm-editor-grid {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }
        .lm-editor-card--location {
            position: sticky;
            top: 1rem;
        }
    }
    .lm-editor-col {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-width: 0;
    }
    .lm-editor-card {
        background: #fff;
        border: 1px solid #eceff3;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .lm-editor-card--location {
        border-color: #dbeafe;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
    }
    .lm-editor-card__head {
        padding: .85rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        background: linear-gradient(180deg, #fffdf9 0%, #fff 100%);
    }
    .lm-editor-card--location .lm-editor-card__head {
        background: linear-gradient(180deg, #eff6ff 0%, #fff 100%);
    }
    .lm-editor-card__title {
        margin: 0;
        font-size: .9rem;
        font-weight: 700;
        color: #7A2E1F;
    }
    .lm-editor-card__body {
        padding: 1rem 1.15rem 1.1rem;
    }
    .lm-editor-code-pill {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .35rem .55rem;
        padding: .55rem .75rem;
        margin-bottom: .85rem;
        border-radius: 10px;
        border: 1px solid #fed7aa;
        background: linear-gradient(135deg, #fffbeb, #fff);
        font-size: .82rem;
    }
    .lm-editor-code-pill__label {
        font-weight: 600;
        color: #92400e;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-size: .68rem;
    }
    .lm-editor-code-pill__value {
        font-family: ui-monospace, monospace;
        font-weight: 700;
        color: #7c2d12;
    }
    .lm-editor-code-pill__note {
        color: #9ca3af;
        font-size: .75rem;
    }
    .lm-editor-field {
        margin-bottom: .85rem;
    }
    .lm-editor-field--last { margin-bottom: 0; }
    .lm-editor-field label {
        display: block;
        font-weight: 600;
        font-size: .86rem;
        color: #1f2937;
        margin-bottom: .35rem;
    }
    .lm-editor-optional {
        font-weight: 500;
        font-size: .72rem;
        color: #9ca3af;
    }
    .lm-editor-input,
    .lm-editor-select,
    .lm-editor-textarea {
        width: 100%;
        box-sizing: border-box;
        padding: .62rem .8rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: .92rem;
        background: #fafafa;
        color: #111827;
        font-family: inherit;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .lm-editor-input:hover,
    .lm-editor-select:hover,
    .lm-editor-textarea:hover {
        border-color: #d1d5db;
        background: #fff;
    }
    .lm-editor-input:focus,
    .lm-editor-select:focus,
    .lm-editor-textarea:focus {
        outline: none;
        border-color: #E8B34B;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(232, 179, 75, 0.22);
    }
    .lm-editor-input--mono {
        font-family: ui-monospace, monospace;
        font-size: .85rem;
    }
    .lm-editor-textarea {
        min-height: 100px;
        resize: vertical;
        line-height: 1.5;
    }
    .lm-editor-select {
        appearance: none;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .75rem center;
        background-size: 1.05rem;
        padding-right: 2.25rem;
    }
    .lm-editor-file-zone {
        border: 1px dashed #d1d5db;
        border-radius: 10px;
        padding: .85rem .9rem;
        background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
    }
    .lm-editor-file-zone:focus-within {
        border-color: #E8B34B;
        background: #fffdf8;
    }
    .lm-editor-file-zone input[type="file"] {
        width: 100%;
        font-size: .85rem;
        cursor: pointer;
    }
    .lm-editor-hint {
        margin: .35rem 0 0;
        font-size: .76rem;
        color: #9ca3af;
    }
    .lm-editor-img-preview {
        margin-top: .75rem;
        position: relative;
        display: inline-block;
        max-width: 100%;
    }
    .lm-editor-img-preview img {
        display: block;
        max-width: 280px;
        width: 100%;
        height: auto;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }
    .lm-editor-img-preview__label {
        display: block;
        margin-top: .35rem;
        font-size: .75rem;
        font-weight: 600;
        color: #6b7280;
    }
    .lm-editor-map-wrap {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #bfdbfe;
        background: #e8edf2;
        margin-bottom: .85rem;
    }
    .lm-editor-map {
        height: 260px;
        width: 100%;
    }
  @media (min-width: 900px) {
        .lm-editor-map { height: 320px; }
    }
    .lm-editor-map-fallback {
        margin: 0 0 .85rem;
        padding: .75rem .85rem;
        border-radius: 10px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        font-size: .82rem;
        color: #6b7280;
        line-height: 1.45;
    }
    .lm-editor-coords { display: grid; gap: .75rem; }
    .lm-editor-coord-display {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .lm-editor-coord-display__item {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .45rem .7rem;
        border-radius: 999px;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        font-size: .82rem;
    }
    .lm-editor-coord-display__k {
        font-weight: 700;
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #4338ca;
    }
    .lm-editor-coord-display__v {
        font-family: ui-monospace, monospace;
        font-weight: 600;
        color: #312e81;
    }
    .lm-editor-coord-fields {
        display: grid;
        gap: .65rem;
        grid-template-columns: 1fr 1fr;
    }
    @media (max-width: 480px) {
        .lm-editor-coord-fields { grid-template-columns: 1fr; }
    }
    .mapboxgl-ctrl-attrib-inner { font-size: 10px; }
</style>
@endonce
