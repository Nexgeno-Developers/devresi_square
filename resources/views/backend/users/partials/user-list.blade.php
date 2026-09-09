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
    <p class="text-muted p-3 mb-0">No contacts yet.</p>
@endforelse

@if($users->hasPages())
    <div class="pagination-wrapper p-3">
        {{ $users->links() }}
    </div>
@endif
