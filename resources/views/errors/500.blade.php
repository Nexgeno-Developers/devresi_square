<x-errors.shell
    code="500"
    title="Something went wrong"
    message="We hit an unexpected error. Try again in a moment. If it keeps happening, contact support."
>
    @auth
        <a class="btn" href="{{ route('backend.dashboard') }}">Back to dashboard</a>
    @else
        <a class="btn" href="{{ route('login') }}">Login</a>
    @endauth
</x-errors.shell>
