@php
    $authUser = auth()->user();
    $billingAccount = null;
    $canViewBilling = false;

    if ($authUser) {
        try {
            $billingAccount = app(\App\Services\Saas\CurrentAccountService::class)->current($authUser);
        } catch (\Throwable $e) {
            $billingAccount = null;
        }

        if ($billingAccount && $authUser->hasRole('Super Admin')) {
            $canViewBilling = true;
        } elseif ($billingAccount && (int) $billingAccount->owner_user_id === (int) $authUser->id) {
            $billingMembership = \App\Models\AccountUser::query()
                ->where('account_id', $billingAccount->id)
                ->where('user_id', $authUser->id)
                ->where('status', 'active')
                ->first();

            $canViewBilling = $billingMembership
                && $billingMembership->can_login
                && in_array($billingMembership->member_type, ['owner', 'admin'], true);
        }
    }
@endphp
<aside id="menu" class="sidebar">
    

    <div class="pt-3 px-3">
        <div class="input-group mb-2">
            <input type="text" id="menu-search" placeholder="Search menu..." class="form-control">
            <button id="reset-search" class="btn btn-outline-secondary" type="button">&times;</button>
        </div>
    </div>

    <ul class="nav flex-column mb-auto pt-2">
        @unless(is_tenant_portal_user() || auth()->user()->hasRole('Contractor'))
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('backend.dashboard') ? 'active' : '' }}"
                href="{{ route('backend.dashboard') }}" >
                <span class="icon_wrapper"><i class="fa-solid fa-tachometer-alt"></i>Dashboard</span>
            </a>
        </li>
        @endunless

        @if(is_tenant_portal_user())
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('backend.home') ? 'active' : '' }}"
                href="{{ route('backend.home') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-house"></i>Home</span>
            </a>
        </li>
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('tenant.tenancy') ? 'active' : '' }}"
                href="{{ route('tenant.tenancy') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-key"></i>My Tenancy</span>
            </a>
        </li>
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('tenant.rent') ? 'active' : '' }}"
                href="{{ route('tenant.rent') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-file-invoice-dollar"></i>Rent &amp; Payments</span>
            </a>
        </li>
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('tenant.calendar') ? 'active' : '' }}"
                href="{{ route('tenant.calendar') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-calendar-check"></i>Calendar</span>
            </a>
        </li>
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('tenant.maintenance') || request()->routeIs('admin.property_repairs.create') ? 'active' : '' }}"
                href="{{ route('tenant.maintenance') }}">
                <span class="icon_wrapper"><i class="bi bi-tools"></i>Maintenance</span>
            </a>
        </li>
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('tenant.documents') ? 'active' : '' }}"
                href="{{ route('tenant.documents') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-folder-open"></i>Documents</span>
            </a>
        </li>
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.users.profile.*') ? 'active' : '' }}"
                href="{{ route('admin.users.profile.show') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-user"></i>Profile</span>
            </a>
        </li>
        @endif

        @unless(is_tenant_portal_user())
        @unless(is_tenant_portal_user() || auth()->user()->hasRole('Contractor'))
        @if(auth()->user()->can('view calendar') || is_landlord_plan_user())
        {{-- Calendar --}}
         <li class="nav-item">
            <a class=" nav-link {{ request()->routeIs('backend.events.calendar') ? 'active' : ''  }}"
                href="{{ route('backend.events.calendar') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-calendar-check"></i>Calendar</span>
            </a>
        </li>
        @endif
        @endunless
        
        @unless(is_tenant_portal_user())
        @canany(['view properties', 'edit properties', 'create properties'])
        {{-- Properties --}}
         <li class="nav-item">
            <a href="#propertiesSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.properties.*') ? 'true' : 'false' }}"
                class="nav-link collapsed {{ request()->routeIs('admin.properties.*') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-building"></i>Properties</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled submenu {{ request()->routeIs('admin.properties.*') ? 'show' : '' }}"
                id="propertiesSubmenu">
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.properties.index') && !request()->routeIs('admin.properties.soft_deleted') ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.properties.index') }} @endslot
                    @slot('link_name') View Active Properties @endslot
                @endcomponent

                @can('view deleted properties')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.properties.soft_deleted') ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.properties.soft_deleted') }} @endslot
                    @slot('link_name') View Deleted Properties @endslot
                @endcomponent
                @endcan

                @can('create properties')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.properties.landlord_wizard.*') || request()->routeIs('admin.properties.quick') ? 'active' : '') }} @endslot
                    @slot('link') {{ property_create_url() }} @endslot
                    @slot('link_name') Add New Property @endslot
                @endcomponent
                @endcan
            </ul>
        </li>
        @endcanany
        @endunless

        @if((auth()->user()->can('manage tenancies') || is_landlord_plan_user()) && ! is_tenant_portal_user() && ! auth()->user()->hasRole('Contractor'))
        {{-- Tenancies --}}
         <li class="nav-item">
            <a href="#tenanciesSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.tenancies.all') || request()->routeIs('admin.tenancies.create') ? 'true' : 'false' }}"
                class="nav-link collapsed {{ request()->routeIs('admin.tenancies.all') || request()->routeIs('admin.tenancies.create') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-home"></i>Tenancies</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled submenu {{ request()->routeIs('admin.tenancies.all') || request()->routeIs('admin.tenancies.create') ? 'show' : '' }}"
                id="tenanciesSubmenu">
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.tenancies.all') && request('status') === 'Active' ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.tenancies.all', ['status' => 'Active']) }} @endslot
                    @slot('link_name') View Active Tenancies @endslot
                @endcomponent

                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.tenancies.all') && request('status') === 'Archived' ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.tenancies.all', ['status' => 'Archived']) }} @endslot
                    @slot('link_name') View Archived Tenancies @endslot
                @endcomponent

                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.tenancies.all') && request('status') === 'Inactive' ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.tenancies.all', ['status' => 'Inactive']) }} @endslot
                    @slot('link_name') View Inactive Tenancies @endslot
                @endcomponent

                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.tenancies.all') && request('status') === 'Terminated' ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.tenancies.all', ['status' => 'Terminated']) }} @endslot
                    @slot('link_name') View Terminated Tenancies @endslot
                @endcomponent

                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.tenancies.create') ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.tenancies.create') }} @endslot
                    @slot('link_name') Add New Tenancy @endslot
                @endcomponent
            </ul>
        </li>
        @endif

        @if(is_landlord_plan_user())
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.owner-groups.*') ? 'active' : '' }}"
                href="{{ route('admin.owner-groups.index') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-people-group"></i>Owner Groups</span>
            </a>
        </li>
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.portal-access.*') ? 'active' : '' }}"
                href="{{ route('admin.portal-access.index') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-user-lock"></i>Portal Access</span>
            </a>
        </li>
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.finance.*') ? 'active' : '' }}"
                href="{{ route('admin.finance.index') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-file-invoice-dollar"></i>Finance</span>
            </a>
        </li>
        @endif

        @unless(is_tenant_portal_user() || is_landlord_plan_user())
        @canany(['view properties', 'create properties'])
        {{-- Sales Offer --}}
         <li class="nav-item">
            <a href="#salesOfferSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.offers.*') ? 'true' : 'false' }}"
                class="nav-link collapsed {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-handshake"></i>Sales Offer</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled submenu {{ request()->routeIs('admin.offers.*') ? 'show' : '' }}"
                id="salesOfferSubmenu">
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.offers.index') && request('status') === 'Pending' ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.offers.index', ['status' => 'Pending']) }} @endslot
                    @slot('link_name') View Active Offers @endslot
                @endcomponent

                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.offers.index') && request('status') === 'Accepted' ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.offers.index', ['status' => 'Accepted']) }} @endslot
                    @slot('link_name') View Archived Offers @endslot
                @endcomponent

                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.offers.create') ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.offers.create') }} @endslot
                    @slot('link_name') Add New Offer @endslot
                @endcomponent
            </ul>
        </li>
        @endcanany
        @endunless

        @php
            $canViewContacts = can_view_contacts();
        @endphp
        @unless(is_tenant_portal_user())
        @if($canViewContacts)
        {{-- Contacts --}}
         <li class="nav-item">
            <a href="#contactsSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.create') ? 'true' : 'false' }}"
                class="nav-link collapsed {{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.create') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-address-book"></i>Contacts</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled submenu {{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.create') ? 'show' : '' }}"
                id="contactsSubmenu">
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.users.index') && request('status') === 'active' ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.users.index', ['status' => 'active']) }} @endslot
                    @slot('link_name') View Active Contacts @endslot
                @endcomponent

                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.users.index') && request('status') === 'inactive' ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.users.index', ['status' => 'inactive']) }} @endslot
                    @slot('link_name') View Archived Contacts @endslot
                @endcomponent

                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.users.create') ? 'active' : '') }} @endslot
                    @slot('link') {{ route('admin.users.create') }} @endslot
                    @slot('link_name') Add New Contact @endslot
                @endcomponent
            </ul>
        </li>
        @endif
        @endunless
        @if($canViewBilling)
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('backend.billing.*') ? 'active' : '' }}"
                href="{{ route('backend.billing.index') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-credit-card"></i>Billing &amp; Plan</span>
            </a>
        </li>
        @endif

        {{-- Registrations (public sign-up approvals) --}}
        @if(auth()->user()->hasRole('Super Admin') && auth()->user()->can('manage registrations'))
         <li class="nav-item">
            <a href="{{ route('admin.registrations.index') }}"
                class="nav-link {{ request()->routeIs('admin.registrations.*') ? 'active' : ''  }}">
                <span class="icon_wrapper">
                    <i class="fa-solid fa-user-plus"></i>Registrations
                    @php $pendingCount = \App\Models\Registration::whereNotIn('status', ['approved','rejected'])->count(); @endphp
                    @if($pendingCount > 0)
                        <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
                    @endif
                </span>
            </a>
        </li>
        @endif

        @if(auth()->user()->hasRole('Super Admin'))
         <li class="nav-item">
            <a href="#saasManagementSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('backend.saas.*') ? 'true' : 'false' }}"
                class="nav-link collapsed {{ request()->routeIs('backend.saas.*') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-layer-group"></i>SaaS Management</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled submenu {{ request()->routeIs('backend.saas.*') ? 'show' : '' }}"
                id="saasManagementSubmenu">
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('backend.saas.plans.*') ? 'active submenu-link' : 'submenu-link' }} @endslot
                    @slot('link') {{ route('backend.saas.plans.index') }} @endslot
                    @slot('link_name') Plans @endslot
                @endcomponent
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('backend.saas.addons.*') ? 'active submenu-link' : 'submenu-link' }} @endslot
                    @slot('link') {{ route('backend.saas.addons.index') }} @endslot
                    @slot('link_name') Addons @endslot
                @endcomponent
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('backend.saas.accounts.*') ? 'active submenu-link' : 'submenu-link' }} @endslot
                    @slot('link') {{ route('backend.saas.accounts.index') }} @endslot
                    @slot('link_name') Accounts @endslot
                @endcomponent
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('backend.saas.subscriptions.*') ? 'active submenu-link' : 'submenu-link' }} @endslot
                    @slot('link') {{ route('backend.saas.subscriptions.index') }} @endslot
                    @slot('link_name') Subscriptions @endslot
                @endcomponent
            </ul>
        </li>
        @endif

        @unless(is_tenant_portal_user())
        @if(auth()->user()->canAny(['view property repair', 'edit property repair', 'create property repair']) || is_landlord_plan_user())
         <li class="nav-item">
            <a href="#repairSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.property_repairs.*') ? 'true' : 'false' }}"
                class="nav-link collapsed {{ request()->routeIs('admin.property_repairs.*') ? 'active' : ''  }}">
                <span class="icon_wrapper"><i class="bi bi-tools"></i>Repair</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled submenu {{ request()->routeIs('admin.property_repairs.*') ? 'show' : '' }}"
                id="repairSubmenu">
                <!-- Raise Repair Issue -->
                @if(auth()->user()->can('create property repair') || is_landlord_plan_user())
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('admin.property_repairs.create') || request()->routeIs('admin.property_repairs.edit') ? 'active submenu-link' : 'submenu-link' }} @endslot
                    @slot('link') {{ route('admin.property_repairs.create') }} @endslot
                    @slot('link_name') Raise Repair Issue @endslot
                @endcomponent
                @endif

                @if(auth()->user()->can('view property repair') || is_landlord_plan_user())
                <!-- Repair Issues Section -->
                <li class="sidebar-sub-list-item py-0 ">
                    <a href="#repairIssuesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('admin.property_repairs.index') || request()->routeIs('admin.property_repairs.index_tabbed') ? 'true' : 'false' }}"
                        class="dropdown-toggle submenu-link{{ request()->routeIs('admin.property_repairs.index') || request()->routeIs('admin.property_repairs.index_tabbed') || request()->routeIs('admin.property_repairs.show') ? 'active' : '' }}">
                        <span class="icon_wrapper">Repair Issues</span>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ request()->routeIs('admin.property_repairs.index') || request()->routeIs('admin.property_repairs.index_tabbed') ? 'show' : '' }}"
                        id="repairIssuesSubmenu">

                        <!-- "All" Status Option -->
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ request()->fullUrl() === route('admin.property_repairs.index') ? 'active submenu-link' : 'submenu-link' }} @endslot
                            @slot('link') {{ route('admin.property_repairs.index') }} @endslot
                            @slot('link_name') All @endslot
                        @endcomponent

                        @unless(is_landlord_plan_user())
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ request()->routeIs('admin.property_repairs.index_tabbed') ? 'active submenu-link' : 'submenu-link' }} @endslot
                            @slot('link') {{ route('admin.property_repairs.index_tabbed') }} @endslot
                            @slot('link_name') Issue List (Tabbed) @endslot
                        @endcomponent
                        @endunless

                        @php
                            $statuses = client_facing_repair_statuses();
                            $currentStatus = request('status');
                        @endphp

                        @foreach($statuses as $status)
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ $currentStatus === $status ? 'active submenu-link' : 'submenu-link' }} @endslot
                            @slot('link') {{ route('admin.property_repairs.index', ['status' => $status]) }} @endslot
                            @slot('link_name') {{ $status }}
                            @endslot
                        @endcomponent
                        @endforeach
                    </ul>
                </li>
                @endif
            </ul>
        </li>
        @endif
        @endunless

        {{-- -- Contractor: only sees Repair Issues -- --}}
        @if(auth()->user()->hasRole('Contractor'))
         <li class="nav-item">
            <a href="#contractorRepairSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('contractor.repairs.*') ? 'true' : 'false' }}"
                class="dropdown-toggle nav-link {{ request()->routeIs('contractor.repairs.*') ? 'active' : ''  }}">
                <span class="icon_wrapper"><i class="fa-solid fa-wrench"></i>Repair Issues</span>
                <i class="fa fa-angle-down"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled submenu {{ request()->routeIs('contractor.repairs.*') ? 'show' : '' }}"
                id="contractorRepairSubmenu">

                @php
                    $contractorStatuses = ['Pending','Reported','Under Process','Work Completed','Invoice Received','Invoice Paid','Closed'];
                    $currentContractorStatus = request('status');
                @endphp

                {{-- All --}}
                <li class="sidebar-sub-list-item py-0 mb-0 ">
                    <a href="{{ route('contractor.repairs.index') }}"
                       class="submenu-link {{ request()->routeIs('contractor.repairs.index') && !request()->filled('status') ? 'active' : '' }}">
                        All
                    </a>
                </li>

                @foreach($contractorStatuses as $status)
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('contractor.repairs.index', ['status' => $status]) }}"
                       class="{{ $currentContractorStatus === $status ? 'active' : '' }}">
                        {{ $status }}
                    </a>
                </li>
                @endforeach
            </ul>
        </li>
        @endif

        @can('view invoices')
        @unless(is_landlord_plan_user())
        @php
            $invoiceStatuses = [
                'all' => 'All Invoices',
                'pending' => 'Pending Invoices',
                'paid' => 'Paid Invoices',
                'overdue' => 'Overdue Invoices',
                'cancelled' => 'Cancelled Invoices'
            ];
            $currentInvoiceStatus = request('status');
        @endphp

         <li class="nav-item">
            <a href="#invoiceSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.invoices.index') ? 'true' : 'false' }}"
                class="nav-link collapsed {{ request()->routeIs('admin.invoices.index') ? 'active' : ''  }}">
                <span class="icon_wrapper"><i class="fas fa-file-invoice-dollar"></i>Invoices</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            <ul class="nav-second-level list-unstyled collapse submenu{{ request()->routeIs('admin.invoices.index') ? 'show' : '' }}"
                id="invoiceSubmenu">
                @foreach($invoiceStatuses as $key => $status)
                    @component('components.backend.common.sidebar-sublink')
                        @slot('class') {{ 'submenu-link' . ($currentInvoiceStatus === $key ? ' active' : '') }} @endslot
                        @slot('link') {{ route('admin.invoices.index', ['status' => $key]) }} @endslot
                        @slot('link_name') {{ $status }}
                        @endslot
                    @endcomponent
                @endforeach
            </ul>
        </li>
        @endunless
        @endcan

        @if(is_landlord_plan_user() || auth()->user()->can('Manage Document Types'))
         <li class="nav-item">
            <a href="{{ route('admin.documents.index') }}" class="nav-link {{ request()->routeIs('admin.documents.*') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-file-alt"></i>Documents</span>
            </a>
        </li>
        @endif
        
        <!-- Transactions -->
        @canany(['view transactions'])
        @unless(is_landlord_plan_user())
             <li class="nav-item">
                <a href="#transactionsSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['backend.transactions.index'], 'true') }}"
                    class="nav-link collapsed {{ areActiveRoutes(['backend.transactions.index']) }}">
                    <span class="icon_wrapper pb_25">
                        <i class="fa-solid fa-money-bill-transfer"></i> Transactions
                    </span>
                    <i class="bi bi-chevron-right"></i>
                </a>

                <ul class="nav-second-level list-unstyled collapse submenu {{ areActiveRoutes(['backend.transactions.index'], 'show') }}"
                    id="transactionsSubmenu">

                    @can('view transactions')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['backend.transactions.index']) }} @endslot
                            @slot('link') {{ route('backend.transactions.index') }} @endslot
                            @slot('link_name') All Transactions @endslot
                        @endcomponent
                    @endcan
                </ul>
            </li>
        @endunless
        @endcanany


       <!-- Website Setup -->
        @canany(['manage website setup', 'manage header', 'manage footer', 'manage appearance'])
        <hr>
         <li class="nav-item">
            <a href="#websiteSetupSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ areActiveRoutes(['website.footer', 'website.header', 'website.appearance'], 'true') }}"
                class="nav-link collapsed {{ areActiveRoutes(['website.footer', 'website.header', 'website.appearance']) }}">
                <span class="icon_wrapper pb_25"><i class="fa-solid fa-cog"></i>Website Setup</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            <ul class="nav-second-level list-unstyled collapse submenu {{ areActiveRoutes(['website.footer', 'website.header', 'website.appearance'], 'show') }}"
                id="websiteSetupSubmenu">
                @can('manage header')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . areActiveRoutes(['website.header']) }} @endslot
                    @slot('link') {{ route('website.header') }} @endslot
                    @slot('link_name') Header
                    @endslot
                @endcomponent
                @endcan
                @can('manage footer')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . areActiveRoutes(['website.footer']) }} @endslot
                    @slot('link') {{ route('website.footer') }} @endslot
                    @slot('link_name') Footer
                    @endslot
                @endcomponent        
                @endcan
                @can('manage appearance')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . areActiveRoutes(['website.appearance']) }} @endslot
                    @slot('link') {{ route('website.appearance') }} @endslot
                    @slot('link_name') Appearance
                    @endslot
                @endcomponent
                @endcan
            </ul>
        </li>
        @endcanany
        <!-- Master Manage -->
        @canany([
            'manage categories','manage branches','manage designations',
            'manage note types','manage document types',
            'manage tenancy types','manage tenancy sub status',
            'manage event types','manage event sub types',
            'manage job types'
        ])

        @php
            $masterManageRoutes = [
                'user-categories.index',
                'admin.branches.index',
                'admin.designations.index',
                'admin.note-types.index',
                'admin.note-types.create',
                'admin.document-types.index',
                'admin.document-types.create',
                'admin.tenancy_types.index',
                'admin.tenancy_types.create',
                'admin.tenancy_sub_statuses.index',
                'admin.tenancy_sub_statuses.create',
                'backend.event_types.index',
                'backend.event_types.create',
                'backend.event_sub_types.index',
                'backend.event_sub_types.create',
                'admin.job_types.index',
                'admin.job_types.create',
                'backend.transaction_categories.index',
                'backend.transaction_categories.create'
            ];
        @endphp

         <li class="nav-item">
            <a href="#masterManageSubmenu" data-bs-toggle="collapse" aria-expanded="{{ areActiveRoutes($masterManageRoutes, 'true') }}" class="nav-link collapsed {{ areActiveRoutes($masterManageRoutes) }}">
                <span class="icon_wrapper pb_25"><i class="fa-solid fa-cogs"></i>Master Manage</span>
                <i class="bi bi-chevron-right"></i>
            </a>
            <ul class="nav-second-level list-unstyled collapse submenu {{ areActiveRoutes($masterManageRoutes, 'show') }}" id="masterManageSubmenu">

                @can('manage categories')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' .areActiveRoutes(['user-categories.index']) }} @endslot
                    @slot('link') {{ route('user-categories.index') }} @endslot
                    @slot('link_name') Categories
                    @endslot
                @endcomponent
                @endcan

                @can('manage branches')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' .  areActiveRoutes(['admin.branches.index']) }} @endslot
                    @slot('link') {{ route('admin.branches.index') }} @endslot
                    @slot('link_name') Branches
                    @endslot
                @endcomponent
                @endcan

                @can('manage designations')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ 'submenu-link' . areActiveRoutes(['admin.designations.index']) }} @endslot
                    @slot('link') {{ route('admin.designations.index') }} @endslot
                    @slot('link_name') Designation
                    @endslot
                @endcomponent
                @endcan

                <!-- Note Types Section -->
                @canany(['manage note types'])
                <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                    <a href="#noteTypesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ areActiveRoutes(['admin.note-types.index', 'admin.note-types.create'], 'true') }}"
                        class="nav-link collapsed {{ areActiveRoutes(['admin.note-types.index', 'admin.note-types.create']) }}">
                        
                        <span class="icon_wrapper">Note Types</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ areActiveRoutes(['admin.note-types.index', 'admin.note-types.create'], 'show') }}"
                        id="noteTypesSubmenu">
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['admin.note-types.index']) }} @endslot
                            @slot('link') {{ route('admin.note-types.index') }} @endslot
                            @slot('link_name') View All
                            @endslot
                        @endcomponent
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' . areActiveRoutes(['admin.note-types.create']) }} @endslot
                            @slot('link') {{ route('admin.note-types.create') }} @endslot
                            @slot('link_name') Add
                            @endslot
                        @endcomponent
                    </ul>
                </li>
                @endcanany
                
                <!-- Document Types -->
                @can('manage document types')
                <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                    <a href="#documentTypesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ areActiveRoutes(['admin.document-types.index', 'admin.document-types.create'], 'true') }}"
                        class="nav-link collapsed {{ areActiveRoutes(['admin.document-types.index', 'admin.document-types.create']) }}">
                        
                        <span class="icon_wrapper">Document Types</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ areActiveRoutes(['admin.document-types.index', 'admin.document-types.create'], 'show') }}"
                        id="documentTypesSubmenu">
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['admin.document-types.index']) }} @endslot
                            @slot('link') {{ route('admin.document-types.index') }} @endslot
                            @slot('link_name') View All
                            @endslot
                        @endcomponent
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' . areActiveRoutes(['admin.document-types.create']) }} @endslot
                            @slot('link') {{ route('admin.document-types.create') }} @endslot
                            @slot('link_name') Add
                            @endslot
                        @endcomponent
                    </ul>
                </li>
                @endcan

                <!-- Tenancy Types Section -->
                @can('manage tenancy types')
                <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                    <a href="#tenancyTypesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ areActiveRoutes(['admin.tenancy_types.index', 'admin.tenancy_types.create'], 'true') }}"
                        class="nav-link collapsed {{ areActiveRoutes(['admin.tenancy_types.index', 'admin.tenancy_types.create']) }}">

                        <span class="icon_wrapper">Tenancy Types</span>
                       <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ areActiveRoutes(['admin.tenancy_types.index', 'admin.tenancy_types.create'], 'show') }}"
                        id="tenancyTypesSubmenu">
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' . areActiveRoutes(['admin.tenancy_types.index']) }} @endslot
                            @slot('link') {{ route('admin.tenancy_types.index') }} @endslot
                            @slot('link_name') View All
                            @endslot
                        @endcomponent
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' . areActiveRoutes(['admin.tenancy_types.create']) }} @endslot
                            @slot('link') {{ route('admin.tenancy_types.create') }} @endslot
                            @slot('link_name') Add
                            @endslot
                        @endcomponent
                    </ul>
                </li>
                @endcan
                <!-- Tenancy Sub Status Section -->
                @can('manage tenancy sub status')
                <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                    <a href="#tenancySubStatusSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ areActiveRoutes(['admin.tenancy_sub_statuses.index', 'admin.tenancy_sub_statuses.create'], 'true') }}"
                        class="nav-link collapsed {{ areActiveRoutes(['admin.tenancy_sub_statuses.index', 'admin.tenancy_sub_statuses.create']) }}">
                        
                        <span class="icon_wrapper">Tenancy Sub Status</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ areActiveRoutes(['admin.tenancy_sub_statuses.index', 'admin.tenancy_sub_statuses.create'], 'show') }}"
                        id="tenancySubStatusSubmenu">
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' . areActiveRoutes(['admin.tenancy_sub_statuses.index']) }} @endslot
                            @slot('link') {{ route('admin.tenancy_sub_statuses.index') }} @endslot
                            @slot('link_name') View All
                            @endslot
                        @endcomponent
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' . areActiveRoutes(['admin.tenancy_sub_statuses.create']) }} @endslot
                            @slot('link') {{ route('admin.tenancy_sub_statuses.create') }} @endslot
                            @slot('link_name') Add
                            @endslot
                        @endcomponent
                    </ul>
                </li>
                @endcan

                <!-- Event Type Section -->
                @can('manage event types')
                <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                    <a href="#eventTypeSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['backend.event_types.index', 'backend.event_types.create'], 'true') }}"
                    class="nav-link collapsed {{ areActiveRoutes(['backend.event_types.index', 'backend.event_types.create']) }}">

                        <span class="icon_wrapper">Event Type</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ areActiveRoutes(['backend.event_types.index', 'backend.event_types.create'], 'show') }}"
                        id="eventTypeSubmenu">

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['backend.event_types.index']) }} @endslot
                            @slot('link') {{ route('backend.event_types.index') }} @endslot
                            @slot('link_name') View All @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['backend.event_types.create']) }} @endslot
                            @slot('link') {{ route('backend.event_types.create') }} @endslot
                            @slot('link_name') Add @endslot
                        @endcomponent

                    </ul>
                </li>
                @endcan

                <!-- Event Sub Type Section -->
                @can('manage event sub types')
                <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                    <a href="#eventSubTypeSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['backend.event_sub_types.index', 'backend.event_sub_types.create'], 'true') }}"
                    class="nav-link collapsed {{ areActiveRoutes(['backend.event_sub_types.index', 'backend.event_sub_types.create']) }}">

                        <span class="icon_wrapper">Event Sub Type</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ areActiveRoutes(['backend.event_sub_types.index', 'backend.event_sub_types.create'], 'show') }}"
                        id="eventSubTypeSubmenu">

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['backend.event_sub_types.index']) }} @endslot
                            @slot('link') {{ route('backend.event_sub_types.index') }} @endslot
                            @slot('link_name') View All @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['backend.event_sub_types.create']) }} @endslot
                            @slot('link') {{ route('backend.event_sub_types.create') }} @endslot
                            @slot('link_name') Add @endslot
                        @endcomponent

                    </ul>
                </li>
                @endcan

                <!-- Job Types Section -->
                @can('manage job types')
                <li class="sidebar-sub-list-item  submenu_wrapper nav-item">
                    <a href="#jobTypesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ areActiveRoutes(['admin.job_types.index', 'admin.job_types.create'], 'true') }}"
                        class="nav-link collapsed {{ areActiveRoutes(['admin.job_types.index', 'admin.job_types.create']) }}">

                        <span class="icon_wrapper">Job Types</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ areActiveRoutes(['admin.job_types.index', 'admin.job_types.create'], 'show') }}"
                        id="jobTypesSubmenu">
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['admin.job_types.index']) }} @endslot
                            @slot('link') {{ route('admin.job_types.index') }} @endslot
                            @slot('link_name') View All
                            @endslot
                        @endcomponent
                    </ul>
                </li>
                @endcan

                <!-- Transaction Categories -->
                @can('manage transaction categories')
                    <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                        <a href="#transactionCategoriesSubmenu" data-bs-toggle="collapse"
                            aria-expanded="{{ areActiveRoutes(['backend.transaction_categories.index', 'backend.transaction_categories.create'], 'true') }}"
                            class="nav-link collapsed {{ areActiveRoutes(['backend.transaction_categories.index', 'backend.transaction_categories.create']) }}">

                            <span class="icon_wrapper">Transaction Categories</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <ul class="nav-third-level collapse list-unstyled submenu {{ areActiveRoutes(['backend.transaction_categories.index', 'backend.transaction_categories.create'], 'show') }}"
                            id="transactionCategoriesSubmenu">

                            @component('components.backend.common.sidebar-sublink')
                                @slot('class') {{ 'submenu-link' .areActiveRoutes(['backend.transaction_categories.index']) }} @endslot
                                @slot('link') {{ route('backend.transaction_categories.index') }} @endslot
                                @slot('link_name') View All @endslot
                            @endcomponent

                            @component('components.backend.common.sidebar-sublink')
                                @slot('class') {{ 'submenu-link' .areActiveRoutes(['backend.transaction_categories.create']) }} @endslot
                                @slot('link') {{ route('backend.transaction_categories.create') }} @endslot
                                @slot('link_name') Add @endslot
                            @endcomponent
                        </ul>
                    </li>
                @endcan
                
            </ul>
        </li>
        @endcanany

        {{-- Super Admin role and permission management --}}
        @if(auth()->user()->hasRole('Super Admin'))
            <li class="nav-item">
                <a href="{{ route('roles.index') }}"
                    class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                    <span class="icon_wrapper">
                        <i class="fa-solid fa-user-shield"></i>Roles &amp; Permissions
                    </span>
                </a>
            </li>
        @endif

        <!-- Staffs -->
        @canany(['view all staffs', 'manage designations'])
             <li class="nav-item">
                <a href="#staffsSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['staffs.index', 'staffs.create', 'staffs.edit', 'admin.designations.index', 'admin.designations.create', 'admin.designations.edit'], 'true') }}"
                    class="nav-link collapsed {{ areActiveRoutes(['staffs.index', 'staffs.create', 'staffs.edit', 'admin.designations.index', 'admin.designations.create', 'admin.designations.edit']) }}">
                    <span class="icon_wrapper pb_25">
                        <i class="fa-solid fa-users"></i> Staffs
                    </span>
                    <i class="bi bi-chevron-right"></i>
                </a>

                <ul class="nav-second-level list-unstyled collapse submenu {{ areActiveRoutes(['staffs.index', 'staffs.create', 'staffs.edit', 'admin.designations.index', 'admin.designations.create', 'admin.designations.edit'], 'show') }}"
                    id="staffsSubmenu">

                    @can('view all staffs')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['staffs.index', 'staffs.create', 'staffs.edit']) }} @endslot
                            @slot('link') {{ route('staffs.index') }} @endslot
                            @slot('link_name') All staffs @endslot
                        @endcomponent
                    @endcan

                    @can('manage designations')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['admin.designations.index', 'admin.designations.create', 'admin.designations.edit']) }} @endslot
                            @slot('link') {{ route('admin.designations.index') }} @endslot
                            @slot('link_name') Designation permissions @endslot
                        @endcomponent
                    @endcan

                </ul>
            </li>
        @endcanany
        <!-- Setup & Configurations -->
        @canany(['view smtp settings'])
             <li class="nav-item">
                <a href="#setupConfigurationsSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['smtp_settings.index'], 'true') }}"
                    class="nav-link collapsed {{ areActiveRoutes(['smtp_settings.index']) }}">
                    <span class="icon_wrapper pb_25">
                        <i class="fa-solid fa-sliders"></i> Setup & Configurations
                    </span>
                    <i class="bi bi-chevron-right"></i>
                </a>

                <ul class="nav-second-level list-unstyled collapse submenu {{ areActiveRoutes(['smtp_settings.index'], 'show') }}"
                    id="setupConfigurationsSubmenu">

                    @can('view smtp settings')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ 'submenu-link' .areActiveRoutes(['smtp_settings.index']) }} @endslot
                            @slot('link') {{ route('smtp_settings.index') }} @endslot
                            @slot('link_name') SMTP Settings @endslot
                        @endcomponent
                    @endcan

                    {{-- Add more settings here if needed --}}
                </ul>
            </li>
        @endcanany
        @unless(is_tenant_portal_user() || auth()->user()->hasRole('Contractor') || is_landlord_plan_user())
         <li class="nav-item">
            <a href="#accountingSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('backend.accounting.*') ? 'true' : 'false' }}"
                class="nav-link collapsed {{ request()->routeIs('backend.accounting.*') ? 'active' : '' }}">
                <span class="icon_wrapper pb_25">
                    <i class="fa-solid fa-calculator"></i> Accounting
                </span>
                <i class="bi bi-chevron-right"></i>
            </a>

            <ul class="nav-second-level list-unstyled collapse submenu {{ request()->routeIs('backend.accounting.*') ? 'show' : '' }}"
                id="accountingSubmenu">

                <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                    <a href="#accountingMastersSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('backend.accounting.masters.*') ? 'true' : 'false' }}"
                        class="nav-link collapsed {{ request()->routeIs('backend.accounting.masters.*') ? 'active' : '' }}">
                        <span class="icon_wrapper">Masters</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ request()->routeIs('backend.accounting.masters.*') ? 'show' : '' }}"
                        id="accountingMastersSubmenu">
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.masters.banks.*') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.masters.banks.index') }}">Banks</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.masters.payment_methods.*') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.masters.payment_methods.index') }}">Payment Method</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.masters.income_categories.*') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.masters.income_categories.index') }}">Income Category</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.masters.expense_categories.*') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.masters.expense_categories.index') }}">Expense Category</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.masters.taxes.*') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.masters.taxes.index') }}">Taxes</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.masters.invoice_headers.*') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.masters.invoice_headers.index') }}">Invoice Headers</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                    <a href="#accountingSaleSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('backend.accounting.sale.*') ? 'true' : 'false' }}"
                        class="nav-link collapsed {{ request()->routeIs('backend.accounting.sale.*') ? 'active' : '' }}">
                        <span class="icon_wrapper">Sale</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ request()->routeIs('backend.accounting.sale.*') ? 'show' : '' }}"
                        id="accountingSaleSubmenu">
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.sale.invoices.*') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.sale.invoices.index') }}">Invoice</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.sale.credit_notes.*') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.sale.credit_notes.index') }}">Credit Notes</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('backend.accounting.receipts.*') || request()->routeIs('backend.accounting.receipts.*') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.receipts.index') }}">
                        <span class="icon_wrapper">Receipts</span>
                    </a>
                </li>
                <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('backend.accounting.statements.customers') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.statements.customers') }}">
                        <span class="icon_wrapper">Customer Statements</span>
                    </a>
                </li>
                <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('backend.accounting.statements.accounts') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.statements.accounts') }}">
                        <span class="icon_wrapper">Account Ledger</span>
                    </a>
                </li>
                <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('backend.accounting.uncharged_repair_work_orders.*') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.uncharged_repair_work_orders.index') }}">
                        <span class="icon_wrapper">Uncharged repair work order</span>
                    </a>
                </li>

                <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                    <a href="#accountingReportsSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('backend.accounting.reports.*') ? 'true' : 'false' }}"
                        class="nav-link collapsed {{ request()->routeIs('backend.accounting.reports.*') ? 'active' : '' }}">
                        <span class="icon_wrapper">Reports</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ request()->routeIs('backend.accounting.reports.*') ? 'show' : '' }}"
                        id="accountingReportsSubmenu">
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.reports.trial_balance') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.reports.trial_balance') }}">Trial Balance</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.reports.profit_loss') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.reports.profit_loss') }}">Profit & Loss</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.reports.balance_sheet') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.reports.balance_sheet') }}">Balance Sheet</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.reports.ar_aging') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.reports.ar_aging') }}">AR Aging</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.reports.ap_aging') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.reports.ap_aging') }}">AP Aging</a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-sub-list-item submenu_wrapper nav-item">
                    <a href="#accountingPaymentsSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('backend.accounting.payments.*') ? 'true' : 'false' }}"
                        class="nav-link collapsed {{ request()->routeIs('backend.accounting.payments.*') ? 'active' : '' }}">
                        <span class="icon_wrapper">Payments</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled submenu {{ request()->routeIs('backend.accounting.payments.*') ? 'show' : '' }}"
                        id="accountingPaymentsSubmenu">
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.payments.incomes') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.payments.incomes') }}">Incomes (Deposit)</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.payments.expenses') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.payments.expenses') }}">Expense</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.payments.general') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.payments.general') }}">General Entry</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.payments.all') || request()->routeIs('backend.accounting.payments.index') ? 'active submenu-link' : 'submenu-link' }}" href="{{ route('backend.accounting.payments.all') }}">All Transactions</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </li>
        @endunless
        <!-- marketing -->
        @canany(['manage email templates'])
             <li class="nav-item">
                <a href="#emailTemplatesSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['email_templates.index'], 'true') }}"
                    class="nav-link collapsed {{ areActiveRoutes(['email_templates.index']) }}">
                    <span class="icon_wrapper pb_25">
                        <i class="fa-solid fa-envelope"></i> Email Templates
                    </span>
                    <i class="bi bi-chevron-right"></i>
                </a>

                <ul class="nav-second-level list-unstyled collapse submenu {{ areActiveRoutes(['email_templates.index'], 'show') }}"
                    id="emailTemplatesSubmenu">

                    @can('manage email templates')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{'submenu-link' . (request()->is('email-templates*') ? 'active' : '') }} @endslot
                            @slot('link') {{ route('email-templates.index', 'all') }} @endslot
                            @slot('link_name') Common Templates @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{'submenu-link' . (request()->is('email-templates*') ? 'active' : '') }} @endslot
                            @slot('link') {{ route('email-templates.index', 'admin') }} @endslot
                            @slot('link_name') Admin Templates @endslot
                        @endcomponent                    

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{'submenu-link' . (request()->is('email-templates*') ? 'active' : '') }} @endslot
                            @slot('link') {{ route('email-templates.index', 'agent') }} @endslot
                            @slot('link_name') Agent Templates @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{'submenu-link' . (request()->is('email-templates*') ? 'active' : '') }} @endslot
                            @slot('link') {{ route('email-templates.index', 'contractor') }} @endslot
                            @slot('link_name') Contractor Templates @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{'submenu-link' . (request()->is('email-templates*') ? 'active' : '') }} @endslot
                            @slot('link') {{ route('email-templates.index', 'owner') }} @endslot
                            @slot('link_name') Owner Templates @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{'submenu-link' . (request()->is('email-templates*') ? 'active' : '') }} @endslot
                            @slot('link') {{ route('email-templates.index', 'tenant') }} @endslot
                            @slot('link_name') Tenant Templates @endslot
                        @endcomponent
                    @endcan

                </ul>
            </li>
        @endcanany


        {{-- @if(!auth()->user()->hasAnyRole(['Super Admin', 'Property Manager']))
             <li class="nav-item">
                <a href="{{ route('user.profile') }}">
                    <span class="icon_wrapper"><i class="fa-solid fa-user"></i>Profile</span>
                </a>
            </li>
        @endif --}}

        @endunless
    </ul>
     <div class="footer_user_profile_menu position-relative">
    
        <div class="pointer" data-bs-toggle="dropdown" aria-expanded="false">
             @if(auth()->user()->profile_picture)
                <img src="{{ asset('storage/' . auth()->user()->profile_picture) }}" alt="Profile" class="rounded-circle profile-img" />
            @else
                <div class="bg-secondary rounded-circle d-flex justify-content-center align-items-center default-profile-icon">
                    <i class="fa-solid fa-user text-white"></i>
                </div>
            @endif

            <div class="flex-grow-1 text-start user-info">
                <h6 class="mb-0 text-truncate" title="{{ $authUser->name }}">{{ $authUser->name }}</h6>
                <small title="{{ $authUser->email }}" class="d-block text-truncate">{{ $authUser->email }}</small>
                <small title="{{ $authUser->access_label }}" class="role-text">{{ $authUser->access_label_type }}: {{ $authUser->access_label }}</small>
            </div>
        </div>

        <ul class="dropdown-menu dropdown-menu-end shadow border-0 user-dropdown-menu">
            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.users.profile.show') }}">
                <i class="fas fa-user fa-fw"></i> My Profile
            </a></li>

            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.users.profile.edit') }}">
                <i class="fas fa-edit fa-fw"></i> Edit Profile
            </a></li>

            <li><hr class="dropdown-divider"></li>

            <li>
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                        <i class="fa-solid fa-sign-out-alt fa-fw"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </div>
</aside>
<div class="backdrop"></div>


