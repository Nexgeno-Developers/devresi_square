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
                        $owners = $ownerGroup->ownerGroupUsers
                            ->map(fn ($membership) => $membership->contact_name ?: $membership->user?->name)
                            ->filter()
                            ->values()
                            ->all();
                        $groupName = count($owners) > 2
                            ? implode(' & ', array_slice($owners, 0, 2)) . ' and others'
                            : implode(' & ', $owners);
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
                <td class="pcc-owner-actions-cell">
                    <div class="pcc-owner-actions">
                        @unless(auth()->user()->hasRole('Tenant'))
                        <button type="button" class="btn btn-sm btn-outline-warning popup-tab-owner-group-edit" title="Edit Owner Group" data-url="{{ route('admin.owner-groups.edit', $ownerGroup->id) }}">
                            <i class="bi bi-pencil" aria-hidden="true"></i><span>Edit</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger pcc-owner-group-delete" title="Delete Owner Group"
                            data-url="{{ route('admin.owner-groups.delete_group', $ownerGroup->id) }}">
                            <i class="bi bi-trash" aria-hidden="true"></i><span>Delete</span>
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
                                    @php
                                        $contact = $user->user;
                                        $name = $user->contact_name ?: $contact?->name ?: 'Co-owner contact';
                                        $email = $user->contact_email ?: $contact?->email;
                                        $phone = $user->contact_phone ?: $contact?->phone;
                                    @endphp
                                    <tr>
                                        <!-- Sr No -->
                                        <td>{{ $userIndex + 1 }}</td>

                                        <!-- Name -->
                                        <td>
                                            {{ $name }}
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
                                            {{ $contact ? ($contact->getRoleNames()->implode(', ') ?: 'Owner') : 'Co-owner contact' }}
                                        </td>

                                        <!-- Phone -->
                                        <td>{{ $phone ?: '—' }}</td>

                                        <!-- Email -->
                                        <td>{{ $email ?: '—' }}</td>

                                        <!-- City -->
                                        <td>{{ $contact?->city ?: '—' }}</td>

                                        <!-- Actions -->
                                        <td>
                                            @if($contact)
                                                <a class="btn btn-sm btn-outline-info me-1" href="{{ route('admin.users.show', $contact->id) }}" title="View contact">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @endif
                                            @if($contact && !$user->is_main)
                                            <button type="button" class="btn btn-sm btn_secondary pcc-owner-set-main"
                                                data-url="{{ route('admin.owner-groups.updateMain', ['id' => $ownerGroup->id]) }}"
                                                data-owner-group-id="{{ $ownerGroup->id }}" data-user-id="{{ $user->id }}">
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

@else
    @if(is_landlord_plan_user())
        <div class="lw-empty">
            <div class="lw-empty-icon"><i class="bi bi-people"></i></div>
            <div class="lw-empty-title">No owners linked</div>
            <p class="mb-3">Owner groups from onboarding appear here. Add one if the title is shared.</p>
            <a href="{{ route('admin.owner-groups.create') }}" class="btn pcc-btn-ink">Add owner group</a>
        </div>
    @else
        <p>No data available</p>
    @endif
@endif
