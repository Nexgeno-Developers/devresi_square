@push('styles')
<style>
#header .btn-soft-danger {
    background-color: #ef486726;
    color: #ef486a;
}

.notification-menu {
    min-width: 360px;
    max-width: min(360px, calc(100vw - 24px));
}

.notification-menu .dropdown-item {
    white-space: normal;
}

#header .rs_logo img {
    max-height: 36px;
    width: auto;
    object-fit: contain;
}
</style>
@endpush
<nav class="navbar">
    <div class="rs-topbar">
        <div class="rs-topbar-left">
            <div class="rs_logo">
                <img src="{{ site_header_logo_url() }}" alt="Resisquare logo">
            </div>
            <div class="toggle_icon_wrapper rs_tooltip hide-menu" data-label="Toggle Side Menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-text-left toggle_icon" viewBox="0 0 16 16" alt="Toggle Menu">
                    <path fill-rule="evenodd" d="M2 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/>
                </svg>
            </div>
            @auth
                @unless(is_tenant_portal_user() || is_landlord_plan_user())
                    <a class="btn btn-soft-danger btn-sm d-flex align-items-center" href="{{ route('cache.clear')}}">
                        <i class="fa-regular fa-hard-drive fs-20"></i>
                        <span class="fw-500 mx-2">Clear Cache</span>
                    </a>
                @endunless
                @if(session()->has('impersonator_user_id'))
                    <span class="small text-muted text-nowrap">Viewing {{ current_account()?->account_name ?: 'customer account' }}</span>
                    <form action="{{ route('backend.accounts.leave-login') }}" method="POST" class="mb-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning text-nowrap">
                            <i class="bi bi-arrow-return-left me-1"></i> Return to Super Admin
                        </button>
                    </form>
                @elseif(! auth()->user()->isSuperAdmin())
                    @php
                        $accountService = app(\App\Services\Saas\CurrentAccountService::class);
                        $currentAccount = $accountService->current(auth()->user());
                        $availableAccounts = $accountService->availableAccounts(auth()->user());
                    @endphp
                    @if($availableAccounts->count() > 1)
                        <form action="{{ route('backend.accounts.switch') }}" method="POST" class="d-flex align-items-center gap-2 mb-0">
                            @csrf
                            <label class="small text-muted mb-0" for="backend-current-account">Account</label>
                            <select id="backend-current-account" name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($availableAccounts as $account)
                                    <option value="{{ $account->id }}" @selected((int) $currentAccount?->id === (int) $account->id)>
                                        {{ $account->account_name ?: 'Account #' . $account->id }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                @endif
            @endauth
        </div>

        <div class="rs-topbar-right">
            @auth
                @php
                    $notificationAccountId = current_account_id();
                    $appointmentNotificationQuery = auth()->user()->unreadNotifications()
                        ->when($notificationAccountId, fn ($query) => $query->where('account_id', $notificationAccountId))
                        ->when(!$notificationAccountId, fn ($query) => $query->whereRaw('1 = 0'));
                    $appointmentNotificationCount = (clone $appointmentNotificationQuery)->count();
                    $appointmentNotifications = $appointmentNotificationQuery->latest()->take(5)->get();
                @endphp
                <div class="dropdown">
                    <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        @if($appointmentNotificationCount)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $appointmentNotificationCount }}
                            </span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-menu p-0">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong>Notifications</strong>
                            @if($appointmentNotificationCount)
                                <button type="button" class="btn btn-link btn-sm p-0" id="mark-all-notifications-read">
                                    Mark all read
                                </button>
                            @endif
                        </div>
                        @forelse($appointmentNotifications as $notification)
                            <a class="dropdown-item py-2 border-bottom notification-item"
                                href="{{ $notification->data['url'] ?? route('backend.events.calendar') }}"
                                data-notification-id="{{ $notification->id }}">
                                <div class="fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                <div class="small text-muted">{{ $notification->data['message'] ?? '' }}</div>
                                <div class="small text-muted mt-1">{{ $notification->created_at?->diffForHumans() }}</div>
                            </a>
                        @empty
                            <div class="px-3 py-3 text-muted small">No unread notifications.</div>
                        @endforelse
                        <div class="px-3 py-2 border-top d-flex justify-content-between">
                            <a href="{{ route('backend.notifications.index') }}" class="small">View all</a>
                            <a href="{{ route('backend.notifications.preferences') }}" class="small">Preferences</a>
                        </div>
                    </div>
                </div>
                @if(is_landlord_plan_user())
                    @php
                        $lwUser = auth()->user();
                        $lwInitials = collect(preg_split('/\s+/', trim((string) $lwUser->name)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');
                    @endphp
                    <div class="dropdown">
                        <a class="lw-account-chip" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                            <span class="lw-account-avatar">{{ $lwInitials ?: 'LL' }}</span>
                            <span class="lw-account-copy">
                                <span class="lw-account-name">{{ $lwUser->name }}</span>
                                <span class="lw-account-plan">{{ current_account()?->account_name ?: 'Landlord' }}</span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li><a class="dropdown-item" href="{{ route('admin.users.profile.show') }}">Profile</a></li>
                            <li><a class="dropdown-item" href="{{ route('backend.notifications.index') }}">Notifications</a></li>
                            <li><a class="dropdown-item" href="{{ route('backend.billing.index') }}">Billing</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    @unless(is_tenant_portal_user())
                        <a class="nav-link px-2" href="{{ route('customer.statements') }}">My Statement</a>
                    @endunless
                    <form action="{{ route('logout') }}" method="POST" class="mb-0">
                        @csrf
                        <button type="submit" class="btn btn-light">Logout</button>
                    </form>
                @endif
            @else
                <a class="nav-link" href="{{ route('login') }}">Login</a>
                <a class="nav-link" href="{{ route('register') }}">Register</a>
            @endauth
        </div>
    </div>
</nav>

@auth
    @push('scripts')
        <script>
            $(document).on('click', '.notification-item', function (event) {
                event.preventDefault();
                const destination = this.href;
                $.post('{{ route('backend.notifications.read', ':notification') }}'.replace(':notification', $(this).data('notification-id')), {
                    _token: $('meta[name="csrf-token"]').attr('content')
                }).always(function () { window.location.href = destination; });
            });

            $(document).on('click', '#mark-all-notifications-read', function () {
                $.post('{{ route('backend.notifications.read_all') }}', {
                    _token: $('meta[name="csrf-token"]').attr('content')
                }).done(function () {
                    window.location.reload();
                });
            });
        </script>
    @endpush
@endauth
