<x-errors.shell
    code="419"
    title="Session expired"
    message="Your form timed out for security. Refresh the page and try again."
>
    <a class="btn" href="javascript:history.back()">Go back</a>
    @auth
        <a class="btn btn-ghost" href="{{ route('backend.dashboard') }}">Dashboard</a>
    @else
        <a class="btn btn-ghost" href="{{ route('login') }}">Login</a>
    @endauth
</x-errors.shell>
