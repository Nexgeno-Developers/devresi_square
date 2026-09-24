<x-errors.shell
    code="404"
    title="Page not found"
    message="That link does not exist, or the page was moved."
>
    @auth
        <a class="btn" href="{{ route('backend.dashboard') }}">Go to dashboard</a>
    @else
        <a class="btn" href="{{ route('login') }}">Login</a>
    @endauth
</x-errors.shell>
