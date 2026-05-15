@extends('backend.layout.app')

@section('content')
    <div class="designations-page">
        <div class="designations-header">
            <div>
                <p class="designations-eyebrow">Staff setup</p>
                <h1>Designations</h1>
                <p class="designations-subtitle">Manage team roles and the permissions attached to each designation.</p>
            </div>

            <a href="{{ route('admin.designations.create') }}" class="btn btn-primary designations-add-btn">
                <i class="bi bi-plus-lg"></i>
                <span>Add Designation</span>
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success designations-alert">
                <i class="bi bi-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="designations-summary">
            <div>
                <span class="summary-label">Total designations</span>
                <strong>{{ $designations->count() }}</strong>
            </div>
            <div>
                <span class="summary-label">Assigned permissions</span>
                <strong>{{ $designations->sum('permissions_count') }}</strong>
            </div>
        </div>

        <div class="designations-table-card">
            <div class="table-responsive">
                <table class="table designations-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="designation-id">#</th>
                            <th scope="col">Title</th>
                            <th scope="col">Permissions</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($designations as $designation)
                            <tr>
                                <td class="designation-id">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="designation-title">{{ $designation->title }}</div>
                                </td>
                                <td>
                                    <span class="permission-badge">
                                        {{ $designation->permissions_count }}
                                        {{ Str::plural('permission', $designation->permissions_count) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="designation-actions">
                                        <a href="{{ route('admin.designations.edit', $designation->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Edit</span>
                                        </a>
                                        <form action="{{ route('admin.designations.destroy', $designation->id) }}" method="POST" onsubmit="return confirm('Delete this designation?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash3"></i>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="designations-empty">
                                        <i class="bi bi-person-badge"></i>
                                        <strong>No designations yet</strong>
                                        <span>Create a designation to start assigning staff permissions.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .designations-page {
            color: #132238;
            padding: 8px 4px 32px;
        }

        .designations-header {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .designations-header h1 {
            color: #132238;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0;
            line-height: 1.2;
            margin: 0;
        }

        .designations-eyebrow {
            color: #6c7484;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .designations-subtitle {
            color: #667085;
            font-size: 14px;
            margin: 8px 0 0;
        }

        .designations-add-btn {
            align-items: center;
            display: inline-flex;
            flex: 0 0 auto;
            gap: 8px;
            justify-content: center;
            min-height: 40px;
            padding: 9px 14px;
            white-space: nowrap;
        }

        .designations-alert {
            align-items: center;
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        .designations-summary {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 220px));
            margin-bottom: 16px;
        }

        .designations-summary > div {
            background: #ffffff;
            border: 1px solid #e6e9ef;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(16, 24, 40, 0.04);
            padding: 14px 16px;
        }

        .designations-summary strong {
            color: #132238;
            display: block;
            font-size: 24px;
            line-height: 1.1;
            margin-top: 4px;
        }

        .summary-label {
            color: #667085;
            display: block;
            font-size: 12px;
            font-weight: 600;
        }

        .designations-table-card {
            background: #ffffff;
            border: 1px solid #e6e9ef;
            border-radius: 8px;
            box-shadow: 0 12px 28px rgba(16, 24, 40, 0.05);
            overflow: hidden;
        }

        .designations-table thead th {
            background: #f7f9fc;
            border-bottom: 1px solid #e6e9ef;
            color: #465366;
            font-size: 12px;
            font-weight: 700;
            padding: 14px 18px;
            text-transform: uppercase;
        }

        .designations-table tbody td {
            border-color: #eef1f5;
            color: #132238;
            padding: 16px 18px;
            vertical-align: middle;
        }

        .designations-table tbody tr:hover {
            background: #fbfcfe;
        }

        .designation-id {
            color: #667085;
            width: 72px;
        }

        .designation-title {
            font-weight: 700;
        }

        .permission-badge {
            align-items: center;
            background: #eef6ff;
            border: 1px solid #cfe6ff;
            border-radius: 999px;
            color: #175cd3;
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            min-height: 28px;
            padding: 5px 10px;
        }

        .designation-actions {
            align-items: center;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .designation-actions form {
            margin: 0;
        }

        .designation-actions .btn {
            align-items: center;
            display: inline-flex;
            gap: 6px;
            min-height: 32px;
            width: auto;
        }

        .designations-empty {
            align-items: center;
            color: #667085;
            display: flex;
            flex-direction: column;
            gap: 6px;
            justify-content: center;
            padding: 42px 16px;
            text-align: center;
        }

        .designations-empty i {
            color: #98a2b3;
            font-size: 30px;
        }

        .designations-empty strong {
            color: #132238;
            font-size: 16px;
        }

        @media (max-width: 767.98px) {
            .designations-header {
                align-items: stretch;
                flex-direction: column;
            }

            .designations-header h1 {
                font-size: 26px;
            }

            .designations-add-btn {
                width: 100%;
            }

            .designations-summary {
                grid-template-columns: 1fr;
            }

            .designations-table thead th,
            .designations-table tbody td {
                padding: 13px 14px;
            }

            .designation-actions {
                justify-content: flex-start;
            }
        }
    </style>
@endpush
