@push('styles')
    <style>
        .staff-permission-count-badge {
            align-items: center;
            background: #eef6ff;
            border: 1px solid #cfe6ff;
            border-radius: 999px;
            color: #175cd3;
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            min-height: 30px;
            padding: 6px 12px;
        }

        .staff-permission-section-header {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .staff-permission-section-header > div {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .staff-permission-section-header small {
            color: #667085;
            font-size: 12px;
            font-weight: 600;
            margin-top: 3px;
        }

        .staff-permission-section-toggle {
            flex: 0 0 auto;
            min-height: 32px;
            white-space: nowrap;
        }

        @media (max-width: 767.98px) {
            .staff-permission-section-header {
                align-items: stretch;
                flex-direction: column;
            }

            .staff-permission-section-toggle {
                width: 100%;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            function updateSelectedPermissionCount() {
                const selectedCount = document.getElementById('selected-permission-count');

                if (!selectedCount) {
                    return;
                }

                selectedCount.textContent = document.querySelectorAll('.custom-permission-checkbox:checked').length;
            }

            function updateSectionToggle(section) {
                const toggle = section.querySelector('.staff-permission-section-toggle');
                const inputs = section.querySelectorAll('.custom-permission-checkbox');

                if (!toggle || !inputs.length) {
                    return;
                }

                const checkedInputs = section.querySelectorAll('.custom-permission-checkbox:checked');
                const allChecked = checkedInputs.length === inputs.length;

                toggle.textContent = allChecked ? 'Disable all' : 'Enable all';
                toggle.classList.toggle('btn-outline-danger', allChecked);
                toggle.classList.toggle('btn-outline-primary', !allChecked);
            }

            window.refreshStaffPermissionTools = function () {
                updateSelectedPermissionCount();
                document.querySelectorAll('.staff-permission-section').forEach(updateSectionToggle);
            };

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.staff-permission-section-toggle').forEach(function (toggle) {
                    toggle.addEventListener('click', function () {
                        const section = toggle.closest('.staff-permission-section');
                        const inputs = section.querySelectorAll('.custom-permission-checkbox');
                        const allChecked = Array.from(inputs).every(function (input) {
                            return input.checked;
                        });

                        inputs.forEach(function (input) {
                            input.checked = !allChecked;
                        });

                        window.refreshStaffPermissionTools();
                    });
                });

                document.querySelectorAll('.custom-permission-checkbox').forEach(function (input) {
                    input.addEventListener('change', window.refreshStaffPermissionTools);
                });

                window.refreshStaffPermissionTools();
            });
        })();
    </script>
@endpush
