@forelse ($users as $user)
    @php
        $nameParts = array_filter([
            $user['first_name'] ?? '',
            $user['middle_name'] ?? '',
            $user['last_name'] ?? '',
        ]);

        $fullName = !empty($user['name'])
            ? $user['name']
            : implode(' ', $nameParts);
    @endphp

    <x-backend.user-card
        class="user-card"
        user-name="{{ $fullName }}"
        email="{{ $user['email'] }}"
        phone="{{ $user['phone'] }}"
        card-style=""
        user-id="{{ $user['id'] }}" />
@empty
    <div class="p-4 text-center">
        <div class="fw-semibold mb-1">No contacts yet</div>
        <p class="text-muted mb-3">Add a tenant or owner so you can link them to tenancies and invites.</p>
        @if(is_landlord_plan_user() || auth()->user()?->can('create contacts'))
            <a href="{{ route('admin.users.create') }}" class="btn btn-outline-primary btn-sm">Add contact</a>
        @endif
    </div>
@endforelse

@if($users->hasPages())
    <div class="pagination-wrapper p-3">
        {{ $users->links() }}
    </div>
@endif
