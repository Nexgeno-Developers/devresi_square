<x-errors.shell
    code="403"
    :title="(isset($exception) && $exception->getMessage()) ? $exception->getMessage() : 'You cannot open this page'"
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
        <a class="btn btn-ghost" href="{{ route('backend.dashboard') }}">Back to dashboard</a>
    @else
        <a class="btn btn-ghost" href="{{ route('login') }}">Login</a>
    @endif
</x-errors.shell>
