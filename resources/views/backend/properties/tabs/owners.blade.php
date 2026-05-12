{{-- Hidden div for property ID --}}
<div id="hidden-property-id" class="d-none" data-property-id="{{ $propertyId }}"></div>

{{-- Show table only if there is data --}}
@if($ownerGroups->isNotEmpty())

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Sr No.</th>
            <th>Group Name</th>
            <th>Purchase Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ownerGroups as $index => $ownerGroup)
            <!-- Main Row -->
            <tr>
                <!-- Sr No -->
                <td>{{ $index + 1 }}</td>

                <!-- Group Name -->
                <td>
                    @php
                        $users = $ownerGroup->ownerGroupUsers->pluck('user.name')->toArray();
                        $groupName = count($users) > 2
                            ? implode(' & ', array_slice($users, 0, 2)) . ' and others'
                            : implode(' & ', $users);
                    @endphp
                    <span class="group-name" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#details-{{ $ownerGroup->id }}" aria-expanded="false" aria-controls="details-{{ $ownerGroup->id }}">
                        {{ $groupName }}
                    </span>
                </td>

                <!-- Purchase Date -->
                <td>{{ $ownerGroup->purchased_date ?? 'N/A' }}</td>

                <!-- Status -->
                <td>{{ ucfirst($ownerGroup->status ?? 'unknown') }}</td>

                <!-- Action -->
                <td>
                    <div class="d-flex justify-content-end">
                        @unless(auth()->user()->hasRole('Tenant'))
                        <button class="btn btn-sm btn-outline-warning popup-tab-owner-group-edit me-1" title="Edit Owner Group" data-url="{{ route('admin.owner-groups.edit', $ownerGroup->id) }}">
                            <i class="bi bi-pencil">Edit</i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger me-1" title="Delete Owner Group"
                            onclick="deleteOwnerGroup('{{ route('admin.owner-groups.delete_group', $ownerGroup->id) }}', this)">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                        @endunless
                    </div>
                </td>
            </tr>

            <!-- Expandable Row for User Details -->
            <tr>
                <td colspan="5" class="p-0">
                    <div id="details-{{ $ownerGroup->id }}" class="collapse">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>City</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ownerGroup->ownerGroupUsers as $userIndex => $user)
                                    <tr>
                                        <!-- Sr No -->
                                        <td>{{ $userIndex + 1 }}</td>

                                        <!-- Name -->
                                        <td>
                                            {{ $user->user->name }}
                                            @if($user->is_main)
                                                <span class="badge text-bg-success">Main</span>
                                            @endif
                                        </td>

                                        <!-- Position -->
                                        {{-- <td>
                                            {{ optional($user->user->category)->name ?? 'N/A' }}
                                        </td> --}}

                                        <!-- Position / Role -->
                                        <td>
                                            {{ $user->user->getRoleNames()->implode(', ') ?: 'N/A' }}
                                        </td>

                                        <!-- Phone -->
                                        <td>{{ $user->user->phone }}</td>

                                        <!-- Email -->
                                        <td>{{ $user->user->email }}</td>

                                        <!-- City -->
                                        <td>{{ $user->user->city }}</td>

                                        <!-- Actions -->
                                        <td>
                                            @if(!$user->is_main)
                                            <button class="btn btn-sm btn_secondary" onclick="setAsMain({{ $user->id }}, {{ $ownerGroup->id }})">
                                                Set as Main
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<!-- JavaScript functions -->
<script>

    function deleteOwnerGroup(url, btn) {
        if (!confirm('Are you sure you want to delete this owner group?')) return;

        btn.disabled = true;

        $.ajax({
            type: 'POST',
            url: url,
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                location.reload();
            },
            error: function (xhr) {
                btn.disabled = false;
                alert('Failed to delete. Please try again.');
            }
        });
    }

    function setAsMain(userId, groupId) {
        if (!confirm('Are you sure you want to set this user as the main user?')) return;

        var actionUrl = "{{ route('admin.owner-groups.updateMain', ['id' => ':groupId']) }}".replace(':groupId', groupId);
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var formData = new FormData();
        formData.append('owner_group_id', groupId);
        formData.append('user_id', userId);
        formData.append('_token', csrfToken);

        $.ajax({
            type: 'POST',
            url: actionUrl,
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.status) {
                    location.reload();
                } else {
                    alert(response.notification || 'Failed to update.');
                }
            },
            error: function () {
                alert('Something went wrong. Please try again.');
            }
        });
    }

</script>



@else
    <p>No data available</p>
@endif
