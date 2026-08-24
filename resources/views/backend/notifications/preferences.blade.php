@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <h2>Notification preferences</h2>
    <p class="text-muted">Statutory, security and issued financial-document emails remain enabled.</p>
    <form method="POST" action="{{ route('backend.notifications.preferences.update') }}">
        @csrf @method('PUT')
        <div class="row mb-3">
            <div class="col-md-4"><label class="form-label">Settings for</label><select name="scope" class="form-select"><option value="user">My notifications</option>@if($canManageAccount)<option value="account">Account defaults</option>@endif</select></div>
            @if($canManageAccount)<div class="col-md-4"><label class="form-label">Account timezone</label><select name="timezone" class="form-select"><option value="Europe/London" @selected($accountTimezone === 'Europe/London')>Europe/London (UK)</option>@foreach(['Europe/Jersey','Europe/Guernsey','Europe/Isle_of_Man'] as $timezone)<option value="{{ $timezone }}" @selected($accountTimezone === $timezone)>{{ $timezone }}</option>@endforeach</select></div>@endif
        </div>
        <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Event</th><th>Category</th><th>Email</th><th>In-app</th></tr></thead><tbody>
        @foreach($definitions as $eventKey => $definition)
            @php($userPref = $userPreferences->get($eventKey))
            @php($accountPref = $accountPreferences->get($eventKey))
            @php($locked = collect($definition['locked_channels'] ?? []))
            <tr><td><input type="hidden" name="preferences[{{ $loop->index }}][event_key]" value="{{ $eventKey }}"><strong>{{ str($eventKey)->replace(['.','_'],' ')->title() }}</strong>@if($locked->isNotEmpty()) <span class="badge bg-info">Required</span>@endif</td><td>{{ ucfirst($definition['category']) }}</td>
            <td><input type="hidden" name="preferences[{{ $loop->index }}][email_enabled]" value="0"><input class="notification-channel-toggle" type="checkbox" name="preferences[{{ $loop->index }}][email_enabled]" value="1" data-user-value="{{ (int) ($locked->contains('email') || ($userPref ? $userPref->email_enabled : ($accountPref ? $accountPref->email_enabled : in_array('email',$definition['channels'])))) }}" data-account-value="{{ (int) ($locked->contains('email') || ($accountPref ? $accountPref->email_enabled : in_array('email',$definition['channels']))) }}" @checked($locked->contains('email') || ($userPref ? $userPref->email_enabled : ($accountPref ? $accountPref->email_enabled : in_array('email',$definition['channels'])))) @disabled($locked->contains('email'))></td>
            <td><input type="hidden" name="preferences[{{ $loop->index }}][in_app_enabled]" value="0"><input class="notification-channel-toggle" type="checkbox" name="preferences[{{ $loop->index }}][in_app_enabled]" value="1" data-user-value="{{ (int) ($locked->contains('system') || ($userPref ? $userPref->in_app_enabled : ($accountPref ? $accountPref->in_app_enabled : in_array('system',$definition['channels'])))) }}" data-account-value="{{ (int) ($locked->contains('system') || ($accountPref ? $accountPref->in_app_enabled : in_array('system',$definition['channels']))) }}" @checked($locked->contains('system') || ($userPref ? $userPref->in_app_enabled : ($accountPref ? $accountPref->in_app_enabled : in_array('system',$definition['channels'])))) @disabled($locked->contains('system'))></td></tr>
        @endforeach
        </tbody></table></div></div>
        <button class="btn btn-primary mt-3">Save preferences</button>
    </form>
    @if($canManageAccount)<a class="btn btn-link px-0 mt-2" href="{{ route('backend.notifications.deliveries') }}">View delivery log</a>@endif
</div>
@endsection

@push('scripts')
<script>
$(document).on('change', 'select[name="scope"]', function () {
    const scope = this.value;
    $('.notification-channel-toggle:not(:disabled)').each(function () {
        this.checked = $(this).attr('data-' + scope + '-value') === '1';
    });
});
</script>
@endpush
