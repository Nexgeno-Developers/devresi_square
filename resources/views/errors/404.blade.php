<x-errors.shell
    code="404"
    title="Page not found"
    message="That link does not exist, or the page was moved."
>
    @auth
        <a class="btn" href="{{ is_tenant_portal_user() ? route('backend.home') : route('backend.dashboard') }}">Back to your workspace</a>
    @else
        <a class="btn" href="{{ route('login') }}">Login</a>
    @endauth
</x-errors.shell>
