@extends('backend.layout.app')

@php
    $archivedCount = $properties->count();
    $recentlyArchivedCount = $properties
        ->filter(fn ($property) => $property->deleted_at?->greaterThanOrEqualTo(now()->subDays(30)))
        ->count();
@endphp

@push('styles')
    <style>
        .archive-properties-page {
            --archive-navy: #14213d;
            --archive-muted: #64748b;
            --archive-border: #e2e8f0;
            --archive-surface: #f8fafc;
            padding: 32px;
        }

        .archive-page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 24px;
        }

        .archive-page-header h1 {
            color: var(--archive-navy);
            font-size: clamp(1.65rem, 3vw, 2.15rem);
            font-weight: 700;
            letter-spacing: -.03em;
            margin: 0 0 6px;
        }

        .archive-page-header p {
            color: var(--archive-muted);
            margin: 0;
            max-width: 680px;
        }

        .archive-back-link {
            align-items: center;
            border: 1px solid var(--archive-border);
            border-radius: 10px;
            color: #334155;
            display: inline-flex;
            flex: 0 0 auto;
            font-weight: 600;
            gap: 8px;
            padding: 10px 14px;
            text-decoration: none;
        }

        .archive-back-link:hover {
            background: #fff;
            color: #0f172a;
        }

        .archive-summary-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 220px));
            margin-bottom: 20px;
        }

        .archive-summary-card {
            align-items: center;
            background: #fff;
            border: 1px solid var(--archive-border);
            border-radius: 14px;
            display: flex;
            gap: 14px;
            padding: 16px;
        }

        .archive-summary-icon {
            align-items: center;
            background: #fff3ed;
            border-radius: 12px;
            color: #ff5a1f;
            display: inline-flex;
            font-size: 1.25rem;
            height: 44px;
            justify-content: center;
            width: 44px;
        }

        .archive-summary-card strong {
            color: var(--archive-navy);
            display: block;
            font-size: 1.4rem;
            line-height: 1;
            margin-bottom: 4px;
        }

        .archive-summary-card span {
            color: var(--archive-muted);
            font-size: .82rem;
        }

        .archive-notice {
            align-items: flex-start;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            color: #854d0e;
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            padding: 14px 16px;
        }

        .archive-notice i {
            font-size: 1.15rem;
            margin-top: 1px;
        }

        .archive-notice strong {
            display: block;
            margin-bottom: 2px;
        }

        .archive-panel {
            background: #fff;
            border: 1px solid var(--archive-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
            overflow: hidden;
        }

        .archive-bulk-bar {
            align-items: center;
            background: var(--archive-surface);
            border-bottom: 1px solid var(--archive-border);
            display: flex;
            justify-content: space-between;
            min-height: 68px;
            padding: 14px 18px;
        }

        .archive-selection-count {
            color: #475569;
            font-size: .9rem;
            font-weight: 600;
        }

        .archive-bulk-actions,
        .archive-row-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .archive-panel .btn {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            font-weight: 600;
            gap: 7px;
            justify-content: center;
        }

        .archive-table-wrap {
            padding: 8px 18px 18px;
        }

        #softDeletedPropertiesTable {
            margin: 0 !important;
            width: 100% !important;
        }

        #softDeletedPropertiesTable thead th {
            border-bottom: 1px solid var(--archive-border);
            color: #475569;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            padding: 15px 12px;
            text-transform: uppercase;
            vertical-align: middle;
        }

        #softDeletedPropertiesTable tbody td {
            border-color: #edf2f7;
            color: #334155;
            padding: 15px 12px;
            vertical-align: middle;
        }

        #softDeletedPropertiesTable tbody tr:hover {
            background: #fafcff;
        }

        .archive-property-ref {
            color: var(--archive-navy);
            display: block;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .archive-property-name,
        .archive-address,
        .archive-date-meta {
            color: var(--archive-muted);
            display: block;
            font-size: .8rem;
        }

        .archive-address {
            line-height: 1.45;
            max-width: 320px;
        }

        .archive-date {
            color: #334155;
            display: block;
            font-size: .85rem;
            font-weight: 600;
        }

        .archive-price {
            color: #0f172a;
            font-weight: 700;
            white-space: nowrap;
        }

        .archive-empty-state {
            color: var(--archive-muted);
            padding: 44px 20px;
            text-align: center;
        }

        .archive-empty-state i {
            color: #94a3b8;
            display: block;
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .archive-properties-page .dataTables_wrapper .dataTables_length,
        .archive-properties-page .dataTables_wrapper .dataTables_filter {
            margin: 12px 0;
        }

        .archive-properties-page .dataTables_wrapper .dataTables_filter input,
        .archive-properties-page .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--archive-border);
            border-radius: 8px;
            min-height: 38px;
        }

        .archive-properties-page .dataTables_info,
        .archive-properties-page .dataTables_paginate {
            color: var(--archive-muted);
            font-size: .85rem;
            padding-top: 16px !important;
        }

        .archive-danger-modal .modal-content {
            border: 0;
            border-radius: 16px;
            overflow: hidden;
        }

        .archive-danger-modal .modal-header {
            background: #fff5f5;
            border-color: #fee2e2;
        }

        .archive-danger-icon {
            align-items: center;
            background: #fee2e2;
            border-radius: 50%;
            color: #dc2626;
            display: inline-flex;
            font-size: 1.25rem;
            height: 44px;
            justify-content: center;
            margin-right: 12px;
            width: 44px;
        }

        .archive-confirm-input {
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        @media (max-width: 767.98px) {
            .archive-properties-page {
                padding: 20px 14px;
            }

            .archive-page-header,
            .archive-bulk-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .archive-summary-grid {
                grid-template-columns: 1fr 1fr;
            }

            .archive-bulk-actions .btn {
                flex: 1 1 auto;
            }

            .archive-table-wrap {
                overflow-x: auto;
                padding-inline: 10px;
            }

            #softDeletedPropertiesTable {
                min-width: 900px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="archive-properties-page">
        <div class="archive-page-header">
            <div>
                <h1>Deleted Properties</h1>
                <p>Restore archived properties with their history intact, or permanently delete records that are no longer required.</p>
            </div>
            <a href="{{ route('admin.properties.index') }}" class="archive-back-link">
                <i class="bi bi-arrow-left"></i>
                Active properties
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Unable to complete the action.</strong>
                {{ $errors->first() }}
            </div>
        @endif

        <div class="archive-summary-grid">
            <div class="archive-summary-card">
                <span class="archive-summary-icon"><i class="bi bi-archive"></i></span>
                <div>
                    <strong>{{ $archivedCount }}</strong>
                    <span>Total archived</span>
                </div>
            </div>
            <div class="archive-summary-card">
                <span class="archive-summary-icon"><i class="bi bi-clock-history"></i></span>
                <div>
                    <strong>{{ $recentlyArchivedCount }}</strong>
                    <span>Archived in the last 30 days</span>
                </div>
            </div>
        </div>

        <div class="archive-notice" role="note">
            <i class="bi bi-shield-exclamation"></i>
            <div>
                <strong>Restore is the recommended option.</strong>
                Permanent deletion cannot be undone and may be blocked when tenancy, repair, accounting, or compliance records are linked.
            </div>
        </div>

        <div class="archive-panel">
            <div class="archive-bulk-bar">
                <span class="archive-selection-count" id="selectionSummary">No properties selected</span>
                <div class="archive-bulk-actions">
                    <button type="button" id="bulkRestoreButton" class="btn btn-primary btn-sm" disabled>
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Restore selected
                    </button>
                    @can('delete properties')
                        <button type="button" id="bulkDeleteButton" class="btn btn-outline-danger btn-sm"
                            data-bs-toggle="modal" data-bs-target="#bulkDeleteModal" disabled>
                            <i class="bi bi-trash3"></i>
                            Delete permanently
                        </button>
                    @endcan
                </div>
            </div>

            <div class="archive-table-wrap">
                <table id="softDeletedPropertiesTable" class="table align-middle">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" class="form-check-input"
                                    aria-label="Select all filtered properties">
                            </th>
                            <th>Property</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Price</th>
                            <th>Archived</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($properties as $property)
                            @php
                                $propertyLabel = $property->prop_ref_no ?: 'Property #' . $property->id;
                                $address = implode(', ', array_filter([
                                    $property->line_1,
                                    $property->line_2,
                                    $property->postcode,
                                ]));
                            @endphp
                            <tr data-id="{{ $property->id }}">
                                <td>
                                    <input type="checkbox" class="form-check-input property-checkbox"
                                        value="{{ $property->id }}" aria-label="Select {{ $propertyLabel }}">
                                </td>
                                <td>
                                    <span class="archive-property-ref">{{ $propertyLabel }}</span>
                                    <span class="archive-property-name">{{ $property->prop_name ?: 'Unnamed property' }}</span>
                                </td>
                                <td>
                                    <span class="archive-address">{{ $address ?: 'Address unavailable' }}</span>
                                </td>
                                <td>{{ $property->city ?: '—' }}</td>
                                <td>
                                    <span class="archive-price">
                                        @if ($property->price !== null)
                                            {{ strtoupper($property->currency ?: 'GBP') }}
                                            {{ number_format((float) $property->price, 2) }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </td>
                                <td data-order="{{ $property->deleted_at?->timestamp }}">
                                    <span class="archive-date">{{ $property->deleted_at?->format('d M Y') }}</span>
                                    <span class="archive-date-meta">{{ $property->deleted_at?->format('h:i A') }}</span>
                                </td>
                                <td>
                                    <div class="archive-row-actions">
                                        <form method="POST" action="{{ route('admin.properties.restore', $property->id) }}"
                                            class="js-restore-form">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                Restore
                                            </button>
                                        </form>
                                        @can('delete properties')
                                            <button type="button" class="btn btn-outline-danger btn-sm js-permanent-delete"
                                                data-bs-toggle="modal" data-bs-target="#permanentDeleteModal"
                                                data-delete-url="{{ route('admin.properties.force-delete', $property->id) }}"
                                                data-property-label="{{ $propertyLabel }}">
                                                <i class="bi bi-trash3"></i>
                                                Delete
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($properties->isEmpty())
                    <div class="archive-empty-state">
                        <i class="bi bi-check-circle"></i>
                        <strong>No deleted properties</strong>
                        <div>Your archive is currently empty.</div>
                    </div>
                @endif
            </div>
        </div>

        <form id="bulkRestoreForm" method="POST" action="{{ route('admin.properties.bulk-restore') }}"
            class="d-none">
            @csrf
            <div class="selected-property-inputs"></div>
        </form>
    </div>

    @can('delete properties')
        <div class="modal fade archive-danger-modal" id="permanentDeleteModal" tabindex="-1"
            aria-labelledby="permanentDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" id="permanentDeleteForm" class="modal-content">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <div class="d-flex align-items-center">
                            <span class="archive-danger-icon"><i class="bi bi-exclamation-triangle"></i></span>
                            <div>
                                <h5 class="modal-title mb-1" id="permanentDeleteModalLabel">Delete permanently?</h5>
                                <small class="text-muted" id="permanentDeletePropertyLabel"></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>This permanently removes the property record. Related records may also be deleted or detached according to their retention rules. This action cannot be undone.</p>
                        <label for="singleDeleteConfirmation" class="form-label">
                            Type <strong>DELETE</strong> to confirm
                        </label>
                        <input type="text" class="form-control archive-confirm-input" id="singleDeleteConfirmation"
                            name="confirmation" autocomplete="off">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="confirmSingleDelete" disabled>
                            <i class="bi bi-trash3"></i>
                            Delete permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade archive-danger-modal" id="bulkDeleteModal" tabindex="-1"
            aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.properties.bulk-force-delete') }}"
                    id="bulkDeleteForm" class="modal-content">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <div class="d-flex align-items-center">
                            <span class="archive-danger-icon"><i class="bi bi-exclamation-triangle"></i></span>
                            <div>
                                <h5 class="modal-title mb-1" id="bulkDeleteModalLabel">Delete selected properties?</h5>
                                <small class="text-muted" id="bulkDeleteCount"></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>The selected records will be permanently removed, and related data may be deleted or detached. If one has protected linked records, the entire operation will be cancelled.</p>
                        <label for="bulkDeleteConfirmation" class="form-label">
                            Type <strong>DELETE</strong> to confirm
                        </label>
                        <input type="text" class="form-control archive-confirm-input" id="bulkDeleteConfirmation"
                            name="confirmation" autocomplete="off">
                        <div class="selected-property-inputs"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="confirmBulkDelete" disabled>
                            <i class="bi bi-trash3"></i>
                            Delete selected permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@section('page.scripts')
    <script>
        $(function () {
            const table = $('#softDeletedPropertiesTable').DataTable({
                pageLength: 10,
                order: [[5, 'desc']],
                columnDefs: [
                    { orderable: false, searchable: false, targets: [0, 6] }
                ],
                language: {
                    emptyTable: 'No deleted properties found',
                    search: '',
                    searchPlaceholder: 'Search archived properties...'
                }
            });

            function selectedIds() {
                return $('.property-checkbox:checked').map(function () {
                    return this.value;
                }).get();
            }

            function populatePropertyInputs(container, ids) {
                const target = $(container).empty();

                ids.forEach(function (id) {
                    $('<input>', {
                        type: 'hidden',
                        name: 'property_ids[]',
                        value: id
                    }).appendTo(target);
                });
            }

            function updateSelectionState() {
                const ids = selectedIds();
                const filteredCheckboxes = $(table.rows({ search: 'applied' }).nodes())
                    .find('.property-checkbox');
                const selectedFiltered = filteredCheckboxes.filter(':checked').length;

                $('#selectionSummary').text(
                    ids.length === 0
                        ? 'No properties selected'
                        : ids.length + ' ' + (ids.length === 1 ? 'property selected' : 'properties selected')
                );
                $('#bulkRestoreButton, #bulkDeleteButton').prop('disabled', ids.length === 0);
                $('#selectAll')
                    .prop('checked', filteredCheckboxes.length > 0 && selectedFiltered === filteredCheckboxes.length)
                    .prop('indeterminate', selectedFiltered > 0 && selectedFiltered < filteredCheckboxes.length);
            }

            $('#selectAll').on('change', function () {
                $(table.rows({ search: 'applied' }).nodes())
                    .find('.property-checkbox')
                    .prop('checked', this.checked);
                updateSelectionState();
            });

            $(document).on('change', '.property-checkbox', updateSelectionState);
            table.on('draw', updateSelectionState);

            $('.js-restore-form').on('submit', function () {
                return window.confirm('Restore this property with its existing history?');
            });

            $('#bulkRestoreButton').on('click', function () {
                const ids = selectedIds();

                if (!ids.length || !window.confirm('Restore ' + ids.length + ' selected properties?')) {
                    return;
                }

                populatePropertyInputs('#bulkRestoreForm .selected-property-inputs', ids);
                $('#bulkRestoreForm').trigger('submit');
            });

            $('#permanentDeleteModal').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget);
                $('#permanentDeleteForm').attr('action', button.data('delete-url'));
                $('#permanentDeletePropertyLabel').text(button.data('property-label'));
                $('#singleDeleteConfirmation').val('');
                $('#confirmSingleDelete').prop('disabled', true);
            });

            $('#singleDeleteConfirmation').on('input', function () {
                $('#confirmSingleDelete').prop('disabled', this.value.trim().toUpperCase() !== 'DELETE');
            });

            $('#bulkDeleteModal').on('show.bs.modal', function (event) {
                const ids = selectedIds();

                if (!ids.length) {
                    event.preventDefault();
                    return;
                }

                $('#bulkDeleteCount').text(
                    ids.length + ' ' + (ids.length === 1 ? 'property selected' : 'properties selected')
                );
                populatePropertyInputs('#bulkDeleteForm .selected-property-inputs', ids);
                $('#bulkDeleteConfirmation').val('');
                $('#confirmBulkDelete').prop('disabled', true);
            });

            $('#bulkDeleteConfirmation').on('input', function () {
                $('#confirmBulkDelete').prop('disabled', this.value.trim().toUpperCase() !== 'DELETE');
            });

            updateSelectionState();
        });
    </script>
@endsection
