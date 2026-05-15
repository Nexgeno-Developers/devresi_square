@extends('backend.layout.app')

@section('content')
@php
    $visibleStaffs = $staffs->getCollection()->filter(fn($staff) => $staff->user !== null)->values();
    $designationCount = $visibleStaffs
        ->map(fn($staff) => $staff->user->designation?->title)
        ->filter()
        ->unique()
        ->count();
@endphp

<div class="staff-index-page">
    <div class="staff-index-header">
        <div>
            <p class="staff-index-eyebrow">Staff setup</p>
            <h1>All Staff</h1>
            <p class="staff-index-subtitle">Manage team members, their designations, and permission access.</p>
        </div>

        @can('add staff')
            <a href="{{ route('staffs.create') }}" class="btn btn-primary staff-add-btn">
                <i class="bi bi-plus-lg"></i>
                <span>Add Staff</span>
            </a>
        @endcan
    </div>

    <div class="staff-summary">
        <div>
            <span class="summary-label">Total staff</span>
            <strong>{{ $staffs->total() }}</strong>
        </div>
        <div>
            <span class="summary-label">Designations on page</span>
            <strong>{{ $designationCount }}</strong>
        </div>
    </div>

    <div class="staff-table-card">
        <div class="table-responsive">
            <table class="table staff-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col" class="staff-id">#</th>
                        <th scope="col">Staff member</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Designation</th>
                        <th scope="col">Access</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visibleStaffs as $staff)
                        <tr>
                            <td class="staff-id">{{ ($staffs->firstItem() ?? 1) + $loop->index }}</td>
                            <td>
                                <div class="staff-person">
                                    <div class="staff-avatar" aria-hidden="true">
                                        {{ Str::of($staff->user->name)->trim()->substr(0, 1)->upper() }}
                                    </div>
                                    <div>
                                        <div class="staff-name">{{ $staff->user->name }}</div>
                                        <div class="staff-muted">ID {{ $staff->user->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="staff-contact">
                                    <span>{{ $staff->user->email }}</span>
                                    @if($staff->user->phone)
                                        <small>{{ $staff->user->phone }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($staff->user->designation)
                                    <span class="designation-badge">{{ $staff->user->designation->title }}</span>
                                @else
                                    <span class="staff-muted">Not assigned</span>
                                @endif
                            </td>
                            <td>
                                <span class="access-badge {{ $staff->permissions_customized ? 'custom' : 'inherited' }}">
                                    {{ $staff->permissions_customized ? 'Custom' : 'Designation' }}
                                </span>
                            </td>
                            <td>
                                <div class="staff-actions">
                                    @can('edit staff')
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('staffs.edit', encrypt($staff->id)) }}" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Edit</span>
                                        </a>
                                    @endcan

                                    @can('delete staff')
                                        <a href="#" class="btn btn-sm btn-outline-danger confirm-delete" data-href="{{ route('staffs.destroy', $staff->id) }}" title="Delete">
                                            <i class="bi bi-trash3"></i>
                                            <span>Delete</span>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="staff-empty">
                                    <i class="bi bi-people"></i>
                                    <strong>No staff found</strong>
                                    <span>Add a staff member to assign designations and permissions.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($staffs->hasPages())
            <div class="staff-pagination">
                {{ $staffs->appends(request()->input())->links() }}
            </div>
        @endif
    </div>
</div>

@endsection

@section('modal')
    @include('backend.modals.delete_modal')
@endsection

@push('styles')
    <style>
        .staff-index-page {
            color: #132238;
            padding: 8px 4px 32px;
        }

        .staff-index-header {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .staff-index-header h1 {
            color: #132238;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0;
            line-height: 1.2;
            margin: 0;
        }

        .staff-index-eyebrow {
            color: #6c7484;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .staff-index-subtitle {
            color: #667085;
            font-size: 14px;
            margin: 8px 0 0;
        }

        .staff-add-btn {
            align-items: center;
            display: inline-flex;
            flex: 0 0 auto;
            gap: 8px;
            justify-content: center;
            min-height: 40px;
            padding: 9px 14px;
            white-space: nowrap;
        }

        .staff-summary {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 220px));
            margin-bottom: 16px;
        }

        .staff-summary > div {
            background: #ffffff;
            border: 1px solid #e6e9ef;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(16, 24, 40, 0.04);
            padding: 14px 16px;
        }

        .staff-summary strong {
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

        .staff-table-card {
            background: #ffffff;
            border: 1px solid #e6e9ef;
            border-radius: 8px;
            box-shadow: 0 12px 28px rgba(16, 24, 40, 0.05);
            overflow: hidden;
        }

        .staff-table thead th {
            background: #f7f9fc;
            border-bottom: 1px solid #e6e9ef;
            color: #465366;
            font-size: 12px;
            font-weight: 700;
            padding: 14px 18px;
            text-transform: uppercase;
        }

        .staff-table tbody td {
            border-color: #eef1f5;
            color: #132238;
            padding: 16px 18px;
            vertical-align: middle;
        }

        .staff-table tbody tr:hover {
            background: #fbfcfe;
        }

        .staff-id {
            color: #667085;
            width: 72px;
        }

        .staff-person {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 220px;
        }

        .staff-avatar {
            align-items: center;
            background: #eef6ff;
            border: 1px solid #cfe6ff;
            border-radius: 50%;
            color: #175cd3;
            display: flex;
            flex: 0 0 38px;
            font-size: 15px;
            font-weight: 800;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .staff-name {
            font-weight: 700;
            line-height: 1.3;
        }

        .staff-muted {
            color: #667085;
            font-size: 12px;
        }

        .staff-contact {
            display: grid;
            gap: 3px;
            min-width: 240px;
        }

        .staff-contact small {
            color: #667085;
        }

        .designation-badge,
        .access-badge {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            min-height: 28px;
            padding: 5px 10px;
        }

        .designation-badge {
            background: #eef6ff;
            border: 1px solid #cfe6ff;
            color: #175cd3;
        }

        .access-badge.inherited {
            background: #ecfdf3;
            border: 1px solid #bbf7d0;
            color: #067647;
        }

        .access-badge.custom {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #b45309;
        }

        .staff-actions {
            align-items: center;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .staff-actions .btn {
            align-items: center;
            display: inline-flex;
            gap: 6px;
            min-height: 32px;
            width: auto;
        }

        .staff-pagination {
            border-top: 1px solid #eef1f5;
            display: flex;
            justify-content: flex-end;
            padding: 14px 18px;
        }

        .staff-empty {
            align-items: center;
            color: #667085;
            display: flex;
            flex-direction: column;
            gap: 6px;
            justify-content: center;
            padding: 42px 16px;
            text-align: center;
        }

        .staff-empty i {
            color: #98a2b3;
            font-size: 30px;
        }

        .staff-empty strong {
            color: #132238;
            font-size: 16px;
        }

        @media (max-width: 767.98px) {
            .staff-index-header {
                align-items: stretch;
                flex-direction: column;
            }

            .staff-index-header h1 {
                font-size: 26px;
            }

            .staff-add-btn {
                width: 100%;
            }

            .staff-summary {
                grid-template-columns: 1fr;
            }

            .staff-table thead th,
            .staff-table tbody td {
                padding: 13px 14px;
            }

            .staff-actions {
                justify-content: flex-start;
            }

            .staff-pagination {
                justify-content: center;
            }
        }
    </style>
@endpush
