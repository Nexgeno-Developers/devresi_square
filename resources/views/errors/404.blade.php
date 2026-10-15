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
    title="Page not found"
    message="That link does not exist, or the page was moved."
>
    @auth
        <a class="btn" href="{{ $home }}">Back</a>
    @else
        <a class="btn" href="{{ route('login') }}">Login</a>
    @endauth
</x-errors.shell>
