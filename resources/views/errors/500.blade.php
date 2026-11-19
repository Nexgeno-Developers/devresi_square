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
    code="500"
    title="Something Went Wrong"
    message="We couldn't complete that action. Try again. If the problem continues, contact support."
>
    @auth
        <a class="btn" href="{{ $home }}">Back to home</a>
    @else
        <a class="btn" href="{{ route('login') }}">Login</a>
    @endauth
</x-errors.shell>
