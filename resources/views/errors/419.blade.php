@php
    // One role-aware "home" for the error pages: contractors go to Jobs, tenants to Home, and so on.
    $home = route('login');
    if (auth()->check()) {
        try {
            $home = route(\App\Support\PostLoginRedirect::routeName(auth()->user()));
        } catch (\Throwable $e) {
            $home = route('login');
        }
    }
@endphp
<x-errors.shell
    code="419"
    title="Session expired"
    message="Your form timed out for security. Refresh the page and try again."
>
    <a class="btn" href="javascript:history.back()">Go back</a>
    @auth
        <a class="btn btn-ghost" href="{{ $home }}">Home</a>
    @else
        <a class="btn btn-ghost" href="{{ route('login') }}">Login</a>
    @endauth
</x-errors.shell>
