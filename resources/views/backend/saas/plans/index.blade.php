@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">SaaS Plans</h2>
        <a href="{{ route('backend.saas.plans.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Plan
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
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
                        <td>{{ $plans->firstItem() + $loop->index }}</td>
                        <td>{{ $plan->name }}</td>
                        <td><code>{{ $plan->code }}</code></td>
                        <td>{{ ucwords(str_replace('_', ' ', $plan->target_account_type)) }}</td>
                        <td>{{ $plan->formattedMonthlyPrice() }}</td>
                        <td>{{ $plan->formattedAnnualPrice() }}</td>
                        <td>{{ $plan->property_limit }}</td>
                        <td>{{ $plan->branch_limit }}</td>
                        <td>{{ $plan->staff_limit }}</td>
                        <td>{{ $plan->trial_days }}</td>
                        <td>
                            <span class="badge bg-{{ $plan->is_active ? 'success' : 'secondary' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $plan->is_active ? 'success' : 'secondary' }}">
                                {{ $plan->is_active ? 'Visible' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="text-center">
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
