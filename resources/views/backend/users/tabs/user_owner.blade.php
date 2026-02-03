<div class="card">
    <div class="card-header">
        <strong>User Owner</strong>
    </div>
    <div class="card-body">
        @if($user->creator)
            <p><strong>Name:</strong> {{ $user->creator->name }}</p>
            <p><strong>Email:</strong> {{ $user->creator->email }}</p>
            <p>
                <strong>Role:</strong>
                @if($user->creator && $user->creator->roles->isNotEmpty())
                    @foreach($user->creator->roles as $role)
                        <span class="badge bg-primary text-uppercase">
                            {{ str_replace('_', ' ', $role->name) }}
                        </span>
                    @endforeach
                @else
                    <span class="text-muted">N/A</span>
                @endif
            </p>

        @else
            <p class="text-muted">No owner assigned to this user.</p>
        @endif
    </div>
</div>
