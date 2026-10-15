@php
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
    code="404"
    title="Record Not Found"
    message="We couldn't find that record. It may have been moved, archived or removed."
>
    @auth
        <a class="btn" href="{{ $home }}">Back to Dashboard</a>
    @else
        <a class="btn" href="{{ route('login') }}">Login</a>
    @endauth
</x-errors.shell>
