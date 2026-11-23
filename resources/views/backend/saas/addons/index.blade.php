@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">SaaS Addons</h2>
        <a href="{{ route('backend.saas.addons.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Addon
        </a>
    </div>

    <div class="table-responsive">
        <table class="rs-stack-table table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Monthly price</th>
                    <th>Annual price</th>
                    <th>Grant quantity</th>
                    <th>Stackable</th>
                    <th>Active</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($addons as $addon)
                    <tr>
                        <td data-label="#">{{ $addons->firstItem() + $loop->index }}</td>
                        <td class="rs-stack-title" data-label="Name">{{ $addon->name }}</td>
                        <td data-label="Code"><code>{{ $addon->code }}</code></td>
                        <td data-label="Type">{{ ucwords(str_replace('_', ' ', $addon->addon_type)) }}</td>
                        <td data-label="Monthly price">{{ $addon->formattedMonthlyPrice() }}</td>
                        <td data-label="Annual price">{{ $addon->formattedAnnualPrice() }}</td>
                        <td data-label="Grant quantity">{{ $addon->grant_quantity }}</td>
                        <td data-label="Stackable">
                            <span class="badge bg-{{ $addon->is_stackable ? 'success' : 'secondary' }}">
                                {{ $addon->is_stackable ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td data-label="Active">
                            <span class="badge bg-{{ $addon->is_active ? 'success' : 'secondary' }}">
                                {{ $addon->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="rs-stack-actions text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('backend.saas.addons.edit', $addon) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>

                                @if((int) $addon->subscription_addons_count === 0)
                                    <form action="{{ route('backend.saas.addons.destroy', $addon) }}" method="POST" onsubmit="return confirm('Delete this addon?');">
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
                        <td colspan="10" class="text-center">No addons found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end mt-3">
        {{ $addons->links() }}
    </div>
</div>
@endsection
