@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal" data-tenant-profile="1">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Profile</p>
            <h1>{{ $user->first_name ?: $user->name }}</h1>
            <p class="tp-muted mb-0">{{ $user->email }}</p>
        </div>
        <a class="tp-btn tp-btn-ghost" href="{{ route('tenant.notifications') }}" data-profile-prefs="1">Notification prefs</a>
    </div>

    <div class="tp-card mb-3" data-tenant-notices="profile">
        <h2>Notices</h2>
        @forelse($notices as $notice)
            <div class="tp-row" data-notice="{{ $notice->id }}">
                <div>
                    <p class="mb-0">{{ $notice->subject }}</p>
                    <p class="tp-muted mb-0">{{ \Illuminate\Support\Str::limit(plain_text($notice->message), 120) }}</p>
                </div>
                @if(! empty($notice->payload['action_url']))
                    <a class="tp-btn tp-btn-ghost" href="{{ $notice->payload['action_url'] }}">Open</a>
                @endif
            </div>
        @empty
            <p class="tp-empty mb-0">No notices yet. Rent, repairs, documents and visits will show here.</p>
        @endforelse
        @if($notices->isNotEmpty())
            <div class="mt-3">
                <a class="tp-btn tp-btn-ghost" href="{{ route('tenant.notifications') }}">All notices</a>
            </div>
        @endif
    </div>

    <form class="tp-card mb-3" method="POST" action="{{ route('tenant.profile.update') }}">
        @csrf
        <h2>Contact details</h2>
        <div class="tp-form-grid">
            <label>
                <span class="tp-metric-label">First name</span>
                <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required maxlength="55">
            </label>
            <label>
                <span class="tp-metric-label">Last name</span>
                <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" maxlength="55">
            </label>
            <label>
                <span class="tp-metric-label">Email</span>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required maxlength="255">
            </label>
            <label>
                <span class="tp-metric-label">Phone</span>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" maxlength="20" placeholder="Optional">
            </label>
        </div>
        <button type="submit" class="tp-btn mt-3">Save contact</button>
    </form>

    <form class="tp-card" method="POST" action="{{ route('tenant.profile.password') }}" data-profile-password="1">
        @csrf
        <h2>Password</h2>
        <div class="tp-form-grid">
            <label>
                <span class="tp-metric-label">Current password</span>
                <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
            </label>
            <label>
                <span class="tp-metric-label">New password</span>
                <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
            </label>
            <label>
                <span class="tp-metric-label">Confirm new password</span>
                <input type="password" name="new_password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
            </label>
        </div>
        <button type="submit" class="tp-btn mt-3">Update password</button>
    </form>
</div>
@endsection
