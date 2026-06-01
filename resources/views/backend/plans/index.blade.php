@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">My Plans</h1>
            <small class="text-muted">Manage subscription plans shown to users</small>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.plans.create') }}" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-plus me-1"></i> Add Plan
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">All Plans</h5>
    </div>
    <div class="card-body p-0">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th>Name</th>
                    <th>Monthly</th>
                    <th>Yearly</th>
                    <th>Properties</th>
                    <th>Staff</th>
                    <th>Tenancies</th>
                    <th class="text-center">Featured</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Order</th>
                    <th width="10%" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $i => $plan)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <p class="fw-semibold mb-0">{{ $plan->name }}</p>
                        @if($plan->badge_label)
                            <span class="badge bg-warning text-dark">{{ $plan->badge_label }}</span>
                        @endif
                        @if($plan->description)
                            <small class="text-muted d-block">{{ Str::limit($plan->description, 60) }}</small>
                        @endif
                    </td>
                    <td>£{{ number_format($plan->price_monthly, 2) }}</td>
                    <td>£{{ number_format($plan->price_yearly, 2) }}</td>
                    <td>{{ $plan->max_properties ?? '∞' }}</td>
                    <td>{{ $plan->max_staff ?? '∞' }}</td>
                    <td>{{ $plan->max_tenancies ?? '∞' }}</td>
                    <td class="text-center">
                        @if($plan->is_featured)
                            <span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>Yes</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <form action="{{ route('admin.plans.toggle', $plan) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm {{ $plan->is_active ? 'btn-success' : 'btn-outline-secondary' }}"
                                    onclick="return confirm('{{ $plan->is_active ? 'Deactivate this plan?' : 'Activate this plan?' }}')">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </td>
                    <td class="text-center">{{ $plan->sort_order }}</td>
                    <td class="text-right">
                        <div class="d-flex justify-content-end" style="gap:4px;">
                            <a href="{{ route('admin.plans.edit', $plan) }}"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.plans.destroy', $plan) }}"
                                  method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this plan?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-4 text-muted">
                        No plans yet. <a href="{{ route('admin.plans.create') }}">Create your first plan</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
