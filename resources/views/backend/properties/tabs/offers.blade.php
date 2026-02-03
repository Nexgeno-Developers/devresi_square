{{-- Hidden div for property ID --}}
<div id="hidden-property-id" class="d-none" data-property-id="{{ $propertyId }}">
    @php
        // Debugging the propertyId
        echo '<pre>';
        var_dump($propertyId);
        echo '</pre>';
    @endphp
</div>

<div class="accordion" id="offersAccordion">
    @if(isset($offers))

    @foreach($offers as $key => $offer)
    @php
        // Check if tenant_details is already an array or string
        $tenantDetails = is_string($offer->tenant_details) ? json_decode($offer->tenant_details, true) : $offer->tenant_details;

        // Collect the user IDs
        $userIds = array_keys($tenantDetails);  // This gives an array of user IDs (e.g., [12, 15, 18])

        // Retrieve the users from the database using the user IDs
        $users = \App\Models\User::whereIn('id', $userIds)->get();

        // Find the main tenant (the user with main_person flag set to true)
        $mainPersonId = null;
        foreach ($tenantDetails as $userId => $isMain) {
            if ($isMain) {
                $mainPersonId = $userId;
                break;
            }
        }

        $mainPerson = $users->firstWhere('id', $mainPersonId);

        // Get the other members (excluding the main tenant)
        $otherMembers = [];
        foreach ($users as $user) {
            if ($user->id != $mainPersonId) {
                $otherMembers[] = $user;
            }
        }
    @endphp

    <div class="accordion-item">
        <h2 class="accordion-header" id="heading-{{ $offer->id }}">
            <button class="accordion-button {{ $key > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $offer->id }}" aria-expanded="{{ $key == 0 ? 'true' : 'false' }}" aria-controls="collapse-{{ $offer->id }}">
                Offer #{{ $key + 1 }} - {{ $offer->status }} (Main Tenant: {{ $mainPerson->name ?? 'N/A' }})
            </button>
        </h2>
        <div id="collapse-{{ $offer->id }}" class="accordion-collapse collapse {{ $key == 0 ? 'show' : '' }}" aria-labelledby="heading-{{ $offer->id }}" data-bs-parent="#offersAccordion">
            <div class="accordion-body">
                <!-- main tenant Section -->
                @if($mainPerson)
                    <h5>Main Tenant</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Employment Status</th>
                                    <th>Price</th>
                                    <th>Deposit</th>
                                    <th>Term</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $mainPerson->name }}</td>
                                    <td>{{ $mainPerson->phone }}</td>
                                    <td>{{ $mainPerson->email }}</td>
                                    <td>{{ $mainPerson->details->employment_status ?? 'N/A' }}</td>
                                    <td>{{ $offer->price }}</td>
                                    <td>{{ $offer->deposit }}</td>
                                    <td>{{ $offer->term }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Other Members Section -->
                @if(!empty($otherMembers))
                    <h5>Other Members</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Employment Status</th>
                                    <th>Price</th>
                                    <th>Deposit</th>
                                    <th>Term</th>
                                    @if ($offer->status !== 'Accepted' && $offer->status !== 'Rejected')
                                    <th>Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($otherMembers as $member)
                                <tr>
                                    <td>{{ $member->name }}</td>
                                    <td>{{ $member->phone }}</td>
                                    <td>{{ $member->email }}</td>
                                    <td>{{ $member->details->employment_status ?? 'N/A' }}</td>
                                    <td>{{ $offer->price }}</td>
                                    <td>{{ $offer->deposit }}</td>
                                    <td>{{ $offer->term }}</td>
                                    @if ($offer->status !== 'Accepted' && $offer->status !== 'Rejected')
                                    <td>
                                        <button class="btn btn-primary btn-sm make-main-btn" data-id="{{ $offer->id }}" data-userid="{{ $member->details['user_id'] }}"  data-member="{{ json_encode($member) }}">Set as Main</button>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="d-flex justify-content-between">
                    <div class="d-flex gap-3">
                        @if ($offer->status !== 'Accepted' && $offer->status !== 'Rejected')
                            <button class="btn btn_secondary btn-sm status-btn" data-id="{{ $offer->id }}" data-status="Accepted">Accept Offer</button>
                            <button class="btn btn-danger btn-sm status-btn" data-id="{{ $offer->id }}" data-status="Rejected">Reject Offer</button>
                        @endif
                    </div>
                    <span class="badge
                        @if ($offer->status == 'Accepted')
                            bg-success
                        @elseif ($offer->status == 'Rejected')
                            bg-danger
                        @else
                            bg-warning
                        @endif
                    ">
                        Status: <span id="status-{{ $offer->id }}">{{ $offer->status }}</span>
                    </span>
                </div>

            </div>
        </div>
    </div>
    @endforeach
    @endif
</div>
