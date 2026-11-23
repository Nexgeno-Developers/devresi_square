@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">SaaS Plans</h2>
        <a href="{{ route('backend.saas.plans.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Plan
        </a>
    </div>

    <div class="table-responsive">
        <table class="rs-stack-table table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Target type</th>
                    <th>Monthly price</th>
                    <th>Annual price</th>
                    <th>Property limit</th>
                    <th>Branch limit</th>
                    <th>Staff limit</th>
                    <th>Trial days</th>
                    <th>Active</th>
                    <th>Pricing visible</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td data-label="#">{{ $plans->firstItem() + $loop->index }}</td>
                        <td class="rs-stack-title" data-label="Name">{{ $plan->name }}</td>
                        <td data-label="Code"><code>{{ $plan->code }}</code></td>
                        <td data-label="Target type">{{ ucwords(str_replace('_', ' ', $plan->target_account_type)) }}</td>
                        <td data-label="Monthly price">{{ $plan->formattedMonthlyPrice() }}</td>
                        <td data-label="Annual price">{{ $plan->formattedAnnualPrice() }}</td>
                        <td data-label="Property limit">{{ $plan->property_limit }}</td>
                        <td data-label="Branch limit">{{ $plan->branch_limit }}</td>
                        <td data-label="Staff limit">{{ $plan->staff_limit }}</td>
                        <td data-label="Trial days">{{ $plan->trial_days }}</td>
                        <td data-label="Active">
                            <span class="badge bg-{{ $plan->is_active ? 'success' : 'secondary' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td data-label="Pricing visible">
                            <span class="badge bg-{{ $plan->is_active ? 'success' : 'secondary' }}">
                                {{ $plan->is_active ? 'Visible' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="rs-stack-actions text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('backend.saas.plans.edit', $plan) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>

                                @if((int) $plan->subscriptions_count === 0)
                                    <form action="{{ route('backend.saas.plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Delete this plan?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center">No plans found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end mt-3">
        {{ $plans->links() }}
    </div>
</div>
@endsection
