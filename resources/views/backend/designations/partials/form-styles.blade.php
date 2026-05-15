@push('styles')
    <style>
        .designation-form-page {
            color: #132238;
            padding: 8px 4px 32px;
        }

        .designation-form-header {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .designation-form-header h1 {
            color: #132238;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0;
            line-height: 1.2;
            margin: 0;
        }

        .designation-form-eyebrow {
            color: #6c7484;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .designation-form-subtitle {
            color: #667085;
            font-size: 14px;
            margin: 8px 0 0;
        }

        .designation-back-btn,
        .designation-form-actions .btn {
            align-items: center;
            display: inline-flex;
            gap: 8px;
            justify-content: center;
            min-height: 40px;
            white-space: nowrap;
        }

        .designation-form-alert {
            align-items: center;
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        .designation-form {
            display: grid;
            gap: 16px;
        }

        .designation-form-card {
            background: #ffffff;
            border: 1px solid #e6e9ef;
            border-radius: 8px;
            box-shadow: 0 12px 28px rgba(16, 24, 40, 0.05);
            padding: 20px;
        }

        .designation-card-header {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .designation-card-header h2 {
            color: #132238;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0;
            line-height: 1.3;
            margin: 0;
        }

        .designation-card-header p {
            color: #667085;
            font-size: 13px;
            margin: 5px 0 0;
        }

        .designation-count-badge {
            background: #eef6ff;
            border: 1px solid #cfe6ff;
            border-radius: 999px;
            color: #175cd3;
            flex: 0 0 auto;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 10px;
        }

        .designation-title-field {
            max-width: 520px;
        }

        .designation-title-field .form-label {
            color: #344054;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .designation-title-field .form-control {
            border-color: #d0d5dd;
            border-radius: 8px;
            min-height: 44px;
        }

        .designation-title-field .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.12);
        }

        .permission-section-list {
            display: grid;
            gap: 14px;
        }

        .permission-section {
            border: 1px solid #eef1f5;
            border-radius: 8px;
            overflow: hidden;
        }

        .permission-section-header {
            align-items: center;
            background: #f7f9fc;
            border-bottom: 1px solid #eef1f5;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px 14px;
        }

        .permission-section-header > div {
            min-width: 0;
        }

        .permission-section-header h3 {
            color: #132238;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0;
            margin: 0;
        }

        .permission-section-header span {
            color: #667085;
            font-size: 12px;
            font-weight: 600;
        }

        .permission-section-toggle {
            flex: 0 0 auto;
            min-height: 32px;
            white-space: nowrap;
        }

        .permission-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            padding: 14px;
        }

        .permission-card {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e6e9ef;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin: 0;
            min-height: 58px;
            padding: 12px 14px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }

        .permission-card:hover {
            border-color: #b9d7ff;
            box-shadow: 0 8px 18px rgba(16, 24, 40, 0.06);
            transform: translateY(-1px);
        }

        .permission-card > span:first-child {
            color: #344054;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.35;
        }

        .permission-card .aiz-switch {
            flex: 0 0 auto;
            margin: 0;
        }

        .designation-form-actions {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 4px;
        }

        @media (max-width: 767.98px) {
            .designation-form-header,
            .designation-card-header,
            .designation-form-actions,
            .permission-section-header {
                align-items: stretch;
                flex-direction: column;
            }

            .designation-form-header h1 {
                font-size: 26px;
            }

            .designation-back-btn,
            .designation-form-actions .btn,
            .permission-section-toggle {
                width: 100%;
            }

            .designation-form-card {
                padding: 16px;
            }

            .permission-grid {
                grid-template-columns: 1fr;
                padding: 12px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectedCount = document.querySelector('[data-selected-permission-count]');
            const sectionToggles = document.querySelectorAll('[data-section-permission-toggle]');
            const permissionInputs = document.querySelectorAll('.permission-card input[type="checkbox"]');

            function updateSelectedCount() {
                if (!selectedCount) {
                    return;
                }

                selectedCount.textContent = document.querySelectorAll('.permission-card input[type="checkbox"]:checked').length;
            }

            function updateSectionToggle(section) {
                const toggle = section.querySelector('[data-section-permission-toggle]');
                const inputs = section.querySelectorAll('.permission-card input[type="checkbox"]');

                if (!toggle || !inputs.length) {
                    return;
                }

                const checkedInputs = section.querySelectorAll('.permission-card input[type="checkbox"]:checked');
                const allChecked = checkedInputs.length === inputs.length;

                toggle.textContent = allChecked ? 'Disable all' : 'Enable all';
                toggle.classList.toggle('btn-outline-danger', allChecked);
                toggle.classList.toggle('btn-outline-primary', !allChecked);
            }

            function refreshSectionToggles() {
                document.querySelectorAll('.permission-section').forEach(updateSectionToggle);
            }

            sectionToggles.forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    const section = toggle.closest('.permission-section');
                    const inputs = section.querySelectorAll('.permission-card input[type="checkbox"]');
                    const allChecked = Array.from(inputs).every(function (input) {
                        return input.checked;
                    });

                    inputs.forEach(function (input) {
                        input.checked = !allChecked;
                    });

                    updateSelectedCount();
                    updateSectionToggle(section);
                });
            });

            permissionInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    updateSelectedCount();
                    updateSectionToggle(input.closest('.permission-section'));
                });
            });

            updateSelectedCount();
            refreshSectionToggles();
        });
    </script>
@endpush
