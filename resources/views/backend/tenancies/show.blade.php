@include('backend.tenancies._record-form-styles')
<div class="rs-workspace">
<div class="card lw-card rs-sheet-card">
<div class="card-body">
<div id="mainForm" class="rs-sheet">
    <section class="rs-section">
        <div class="rs-section-head">
            <div>
                <h2>Dates</h2>
            </div>
        </div>
        <div class="rs-facts" data-let-facts="landlord">
            <div>
                <span>Move in</span>
                <strong>{{ rs_date($tenancy->move_in) }}</strong>
            </div>
            <div>
                <span>Term</span>
                <strong>{{ $tenancy->displayTerm() }}</strong>
            </div>
            <div>
                <span>Move out</span>
                <strong>{{ rs_date($tenancy->move_out) }}</strong>
            </div>
        </div>
    </section>

    <section class="rs-section">
        <div class="rs-section-head">
            <div>
                <h2>Rent</h2>
            </div>
        </div>
        <div class="rs-facts">
            <div>
                <span>Rent</span>
                <strong>{{ rs_money($tenancy->rent) }}</strong>
            </div>
            <div>
                <span>How often</span>
                <strong>{{ $tenancy->rentFrequencyLabel() }}</strong>
            </div>
            <div>
                <span>Due day</span>
                <strong>{{ $tenancy->rentDueLabel() }}</strong>
            </div>
            <div>
                <span>Deposit</span>
                <strong>£{{ number_format($tenancy->deposit, 2) }}</strong>
            </div>
        </div>
    </section>

    @include('backend.tenancies._deposit-protection')

    <section class="rs-section">
        <div class="rs-section-head">
            <div>
                <h2>Right to rent follow-up register</h2>
                <p>Reminder text never includes immigration details.</p>
            </div>
        </div>
        @foreach($tenancy->tenantMembers as $member)
            <form method="POST" action="{{ route('admin.tenancies.right-to-rent.update', [$tenancy, $member]) }}" class="rs-grid mb-3">
                @csrf @method('PUT')
                <div class="rs-field rs-span-2">
                    <span>Tenant</span>
                    <strong>{{ $member->user?->name ?? 'Tenant' }}</strong>
                </div>
                <label class="rs-check rs-span-2">
                    <input type="hidden" name="right_to_rent_required" value="0">
                    <input class="form-check-input" type="checkbox" name="right_to_rent_required" value="1" @checked($member->right_to_rent_required)>
                    <span>Follow-up required</span>
                </label>
                <label class="rs-field rs-span-2">
                    Checked at
                    <input type="datetime-local" name="right_to_rent_checked_at" class="form-control" value="{{ $member->right_to_rent_checked_at?->format('Y-m-d\TH:i') }}">
                </label>
                <label class="rs-field rs-span-2">
                    Follow-up due
                    <input type="datetime-local" name="right_to_rent_follow_up_due_at" class="form-control" value="{{ $member->right_to_rent_follow_up_due_at?->format('Y-m-d\TH:i') }}">
                </label>
                <div class="rs-span-6 rs-actions">
                    <button class="btn btn-sm btn-outline-primary">Save</button>
                </div>
            </form>
        @endforeach
    </section>

    @unless(is_landlord_plan_user())
    <section class="rs-section">
        <div class="rs-section-head">
            <div>
                <h2>Assignment</h2>
            </div>
        </div>
        <div class="rs-people">
            @forelse($tenancy->propertyManagers as $manager)
                <div class="rs-person"><p class="mb-0">{{ $manager->name }}</p></div>
            @empty
                <p class="text-muted mb-0">No property manager assigned.</p>
            @endforelse
        </div>
    </section>
    @endunless

    <section class="rs-section">
        <div class="rs-section-head">
            <div>
                <h2>Notice register</h2>
            </div>
        </div>
        <table class="table lw-table table-sm">
            <thead><tr><th>Type</th><th>Served</th><th>Effective</th><th>Recipient</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($tenancy->notices()->with('recipient')->latest('served_at')->get() as $notice)
                <tr>
                    <td>{{ beautify_string($notice->notice_type) }}</td>
                    <td>{{ $notice->served_at?->timezone(current_account()?->timezone ?? 'Europe/London')->format('d/m/Y H:i') }}</td>
                    <td>{{ $notice->effective_at?->timezone(current_account()?->timezone ?? 'Europe/London')->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $notice->recipient?->name ?? 'All tenants' }}</td>
                    <td>{{ ucfirst($notice->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No notices recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
        <form method="POST" action="{{ route('admin.tenancies.notices.store', $tenancy) }}" class="rs-grid">
            @csrf
            <label class="rs-field rs-span-3">
                Notice type
                <select name="notice_type" class="form-select" required>
                    <option value="">Choose a type</option>
                    <option value="written_information">Written information</option>
                    <option value="rent_increase">Rent increase</option>
                    <option value="tenant_notice">Tenant notice</option>
                    <option value="possession_notice">Possession notice</option>
                </select>
            </label>
            <label class="rs-field rs-span-3">
                Recipient
                <select name="recipient_user_id" class="form-select">
                    <option value="">All tenants</option>
                    @foreach($tenancy->tenantMembers as $member)
                        <option value="{{ $member->user_id }}">{{ $member->user?->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="rs-field rs-span-3">
                Served
                <input type="datetime-local" name="served_at" class="form-control" required>
            </label>
            <label class="rs-field rs-span-3">
                Effective
                <input type="date" name="effective_at" class="form-control">
            </label>
            <label class="rs-field rs-span-6">
                Note
                <textarea name="notes" class="form-control" rows="2" placeholder="Evidence or internal notes"></textarea>
            </label>
            <div class="rs-span-6 rs-actions">
                <button class="btn lw-btn-primary">Record and notify</button>
            </div>
        </form>
    </section>
</div>
</div>
</div>
<aside class="rs-rail">
    <div class="card lw-card">
        <div class="card-body">
            <section class="rs-section">
                <div class="rs-section-head">
                    <div>
                        <h2>Household</h2>
                        <p>The home and the people on this let.</p>
                    </div>
                </div>
                <div class="rs-facts">
                    <div class="is-wide">
                        <span>Property</span>
                        <strong>{{ $tenancy->property?->full_address ?? 'Not linked' }}</strong>
                    </div>
                    <div>
                        <span>Status</span>
                        <strong>{{ $tenancy->status }}</strong>
                    </div>
                </div>
                <div class="rs-people mt-3">
                    @forelse($tenancy->tenantMembers as $member)
                        <div class="rs-person">
                            <p>
                                {{ $member->user->name ?? 'Tenant' }}
                                @if($member->is_main_person)
                                    <span class="badge bg-success ms-1">Lead tenant</span>
                                @endif
                                <br>
                                <small>
                                    {{ $member->user?->email }}
                                    @if($member->user?->phone)
                                        · {{ $member->user->phone }}
                                    @endif
                                </small>
                            </p>
                            @if($tenancy->status === 'Active' && $member->user_id && \App\Support\Messaging\MessagingBadge::area() === \App\Support\Messaging\MessagingArea::ADMIN)
                                @include('messaging.partials.start-button', ['area' => 'admin', 'context' => 'tenant', 'id' => $member->id, 'label' => 'Message'])
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No tenants linked yet.</p>
                    @endforelse
                </div>
            </section>
            @include('backend.tenancies._correction-requests')
        </div>
    </div>
</aside>
</div>
