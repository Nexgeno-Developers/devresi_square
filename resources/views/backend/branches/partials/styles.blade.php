@push('styles')
<style>
    .branch-form-page {
        color: #132238;
        padding: 8px 4px 32px;
    }

    .branch-form-header {
        align-items: flex-start;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .branch-form-header h1 {
        color: #132238;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: 0;
        line-height: 1.2;
        margin: 0;
    }

    .branch-form-header .btn {
        align-items: center;
        display: inline-flex;
        gap: 8px;
        white-space: nowrap;
    }

    .branch-form-eyebrow {
        color: #6c7484;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0;
        margin: 0 0 4px;
        text-transform: uppercase;
    }

    .branch-form-subtitle {
        color: #667085;
        font-size: 14px;
        margin: 8px 0 0;
    }

    .branch-form-card {
        background: #fff;
        border: 1px solid #e6e9ef;
        border-radius: 8px;
        box-shadow: 0 12px 28px rgba(16, 24, 40, 0.05);
        overflow: hidden;
    }

    .branch-form-section {
        border-bottom: 1px solid #eef1f5;
        padding: 22px 24px;
    }

    .branch-form-section:last-of-type {
        border-bottom: 0;
    }

    .branch-section-title {
        margin-bottom: 16px;
    }

    .branch-section-title h2 {
        color: #132238;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 0;
        margin: 0;
    }

    .branch-section-title p {
        color: #667085;
        font-size: 13px;
        margin: 5px 0 0;
    }

    .branch-form-card .form-label {
        color: #344054;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .branch-form-card .form-control {
        border-color: #d0d5dd;
        border-radius: 6px;
        min-height: 40px;
    }

    .branch-form-card .form-control[readonly] {
        background: #f7f9fc;
        color: #667085;
    }

    .branch-form-actions {
        align-items: center;
        background: #f7f9fc;
        border-top: 1px solid #eef1f5;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding: 16px 24px;
    }

    @media (max-width: 767.98px) {
        .branch-form-header {
            flex-direction: column;
        }

        .branch-form-header .btn {
            justify-content: center;
            width: 100%;
        }

        .branch-form-section,
        .branch-form-actions {
            padding: 18px 16px;
        }

        .branch-form-actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }
    }
</style>
@endpush
