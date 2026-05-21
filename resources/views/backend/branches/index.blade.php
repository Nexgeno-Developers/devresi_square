@extends('backend.layout.app')

@section('content')
@php
    $branchCount = $branches->count();
    $mainHeadOffice = $branches->firstWhere('is_main_head_office', true);
    $withEmail = $branches->filter(fn($branch) => filled($branch->user_email))->count();
    $withPhone = $branches->filter(fn($branch) => filled($branch->user_phone))->count();
@endphp

<div class="branch-page">
    <div class="branch-header">
        <div>
            <p class="branch-eyebrow">Company setup</p>
            <h1>Branches</h1>
            <p class="branch-subtitle">Manage office locations mapped to your company.</p>
        </div>
        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary branch-add-btn">
            <i class="bi bi-plus-lg"></i>
            <span>Add Branch</span>
        </a>
    </div>

    <div class="branch-summary">
        <div>
            <span>Total branches</span>
            <strong>{{ $branchCount }}</strong>
        </div>
        <div>
            <span>With email</span>
            <strong>{{ $withEmail }}</strong>
        </div>
        <div>
            <span>Main office</span>
            <strong>{{ $mainHeadOffice?->name ?? 'Not set' }}</strong>
        </div>
    </div>

    <div class="branch-table-card">
        <div class="table-responsive">
            <table class="table branch-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($branches as $branch)
                        @php
                            $address = implode(', ', array_filter([
                                $branch->address_line_1 ?: $branch->address,
                                $branch->address_line_2,
                                $branch->city,
                                $branch->county,
                                $branch->postcode,
                                $branch->country,
                            ]));
                        @endphp
                        <tr>
                            <td>
                                <div class="branch-name">{{ $branch->name }}</div>
                                @if($branch->is_main_head_office)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1">Main Head Office</span>
                                @endif
                                <div class="branch-muted">ID {{ $branch->id }}</div>
                            </td>
                            <td>
                                <div class="branch-address">{{ $address ?: 'Address not set' }}</div>
                            </td>
                            <td>
                                <div class="branch-contact">
                                    <span>{{ $branch->user_email ?: 'No email' }}</span>
                                    <small>{{ $branch->user_phone ?: 'No phone' }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="branch-actions">
                                    <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Edit</span>
                                    </a>
                                    <form action="{{ route('admin.branches.destroy', $branch) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this branch?')">
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
                                <div class="branch-empty">
                                    <i class="bi bi-building"></i>
                                    <strong>No branches found</strong>
                                    <span>Add your first branch to map staff and company contact details.</span>
                                    <a href="{{ route('admin.branches.create') }}" class="btn btn-primary btn-sm">Add Branch</a>
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
    .branch-page {
        color: #132238;
        padding: 8px 4px 32px;
    }

    .branch-header {
        align-items: flex-start;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .branch-header h1 {
        color: #132238;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: 0;
        line-height: 1.2;
        margin: 0;
    }

    .branch-eyebrow {
        color: #6c7484;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0;
        margin: 0 0 4px;
        text-transform: uppercase;
    }

    .branch-subtitle {
        color: #667085;
        font-size: 14px;
        margin: 8px 0 0;
    }

    .branch-add-btn,
    .branch-actions .btn {
        align-items: center;
        display: inline-flex;
        gap: 8px;
        justify-content: center;
        white-space: nowrap;
    }

    .branch-summary {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(3, minmax(0, 180px));
        margin-bottom: 16px;
    }

    .branch-summary > div {
        background: #fff;
        border: 1px solid #e6e9ef;
        border-radius: 8px;
        box-shadow: 0 10px 24px rgba(16, 24, 40, 0.04);
        padding: 14px 16px;
    }

    .branch-summary span,
    .branch-muted {
        color: #667085;
        font-size: 12px;
    }

    .branch-summary strong {
        color: #132238;
        display: block;
        font-size: 24px;
        line-height: 1.1;
        margin-top: 4px;
    }

    .branch-table-card {
        background: #fff;
        border: 1px solid #e6e9ef;
        border-radius: 8px;
        box-shadow: 0 12px 28px rgba(16, 24, 40, 0.05);
        overflow: hidden;
    }

    .branch-table thead th {
        background: #f7f9fc;
        border-bottom: 1px solid #e6e9ef;
        color: #465366;
        font-size: 12px;
        font-weight: 700;
        padding: 14px 18px;
        text-transform: uppercase;
    }

    .branch-table tbody td {
        border-color: #eef1f5;
        padding: 16px 18px;
    }

    .branch-name {
        font-weight: 700;
    }

    .branch-address {
        color: #344054;
        max-width: 460px;
    }

    .branch-contact {
        display: grid;
        gap: 3px;
        min-width: 220px;
    }

    .branch-contact small {
        color: #667085;
    }

    .branch-actions {
        align-items: center;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    .branch-actions form {
        margin: 0;
    }

    .branch-empty {
        align-items: center;
        color: #667085;
        display: flex;
        flex-direction: column;
        gap: 8px;
        justify-content: center;
        padding: 42px 16px;
        text-align: center;
    }

    .branch-empty i {
        color: #98a2b3;
        font-size: 30px;
    }

    .branch-empty strong {
        color: #132238;
        font-size: 16px;
    }

    @media (max-width: 767.98px) {
        .branch-header {
            flex-direction: column;
        }

        .branch-add-btn {
            width: 100%;
        }

        .branch-summary {
            grid-template-columns: 1fr;
        }

        .branch-actions {
            justify-content: flex-start;
        }
    }
</style>
@endpush
