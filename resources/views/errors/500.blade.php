<x-errors.shell
    code="500"
    title="Something Went Wrong"
    message="We couldn't complete that action. Try again. If the problem continues, contact support."
>
    @auth
        <a class="btn" href="{{ route('backend.dashboard') }}">Back to dashboard</a>
    @else
        <a class="btn" href="{{ route('login') }}">Login</a>
    @endauth
</x-errors.shell>
