@php
    $propertyType = strtolower(trim((string) ($property->property_type ?? '')));
    $responsibilityTypes = ['property_manager' => 'Property Manager'];

    if (in_array($propertyType, ['sales', 'both'], true)) {
        $responsibilityTypes += [
            'sales_consultant' => 'Sales Consultant',
            'sales_manager' => 'Sales Manager',
        ];
    }

    if (in_array($propertyType, ['lettings', 'both'], true)) {
        $responsibilityTypes += [
            'lettings_consultant' => 'Lettings Consultant',
            'lettings_manager' => 'Lettings Manager',
        ];
    }

    $mappedStaff = $responsibilities->keyBy('responsibility_type');
@endphp

<form id="propertyResponsibilityForm">
    @csrf
    <input type="hidden" name="property_id" value="{{ $property->id }}">
    <input type="hidden" name="form_type" value="responsibility">

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Responsibility Type</th>
                    <th>Staff</th>
                </tr>
            </thead>
            <tbody>
                @foreach($responsibilityTypes as $type => $label)
                    @php $selectedStaffId = $mappedStaff->get($type)?->user_id; @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td>
                            <select name="responsibility_staff[{{ $type }}]" class="form-control">
                                <option value="">Not assigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected($selectedStaffId == $user->id)>
                                        {{ $user->id }} - {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn_secondary">Save Changes</button>
    </div>
</form>
