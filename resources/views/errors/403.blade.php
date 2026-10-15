@php
    $rawForbidden = isset($exception) ? trim((string) $exception->getMessage()) : '';
    $forbiddenTitle = ($rawForbidden !== '' && ! in_array($rawForbidden, ['This action is unauthorized.', 'This action is unauthorized'], true))
        ? $rawForbidden
        : 'You cannot open this page';
    $home = route('login');
    if (auth()->check()) {
        $home = match (true) {
            auth()->user()->hasRole('Contractor') => route('contractor.jobs.index'),
            is_tenant_portal_user() => route('backend.home'),
            default => route('backend.dashboard'),
        };
    }
@endphp
<x-errors.shell
    code="403"
    :title="$forbiddenTitle"
    :message="session()->has('impersonator_user_id')
        ? 'You are viewing a customer account. Return to Super Admin, or open a page allowed on this plan.'
        : 'This area belongs to another account or role.'"
>
    @if(session()->has('impersonator_user_id'))
        <form action="{{ route('backend.accounts.leave-login') }}" method="POST">
            @csrf
            <button type="submit" class="btn">Return to Super Admin</button>
        </form>
    @elseif(auth()->check())
        <a class="btn btn-ghost" href="{{ $home }}">Back</a>
    @else
        <a class="btn btn-ghost" href="{{ route('login') }}">Login</a>
    @endif
</x-errors.shell>
