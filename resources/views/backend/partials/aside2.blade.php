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
        } elseif ($billingAccount) {
            $billingMembership = \App\Models\AccountUser::query()
                ->where('account_id', $billingAccount->id)
                ->where('user_id', $authUser->id)
                ->where('status', 'active')
                ->first();

            $canViewBilling = $billingMembership
                && $billingMembership->can_login
                && in_array($billingMembership->member_type, ['owner', 'admin'], true)
                && $billingMembership->access_level === 'full';
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
        @unless(auth()->user()->hasRole('Tenant') || auth()->user()->hasRole('Contractor'))
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('backend.dashboard') ? 'active' : '' }}"
                href="{{ route('backend.dashboard') }}" >
                <span class="icon_wrapper"><i class="fa-solid fa-tachometer-alt"></i>Dashboard</span>
            </a>
        </li>
        @endunless

        @if($canViewBilling)
         <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('backend.billing.*') ? 'active' : '' }}"
                href="{{ route('backend.billing.index') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-credit-card"></i>Billing &amp; Plan</span>
            </a>
        </li>
        @endif

        @can('view calendar')
        {{-- Calendar --}}
         <li class="nav-item">
            <a class=" nav-link {{ request()->routeIs('backend.events.calendar') ? 'active' : ''  }}"
                href="{{ route('backend.events.calendar') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-calendar-check"></i>Calendar</span>
            </a>
        </li>
        @endcan
        
        @canany(['view properties', 'edit properties', 'create properties'])
        {{-- Users --}}
         <li class="nav-item">
            @if(auth()->user()->hasRole('Tenant'))
                {{-- Tenant: simple direct link, no dropdown --}}
                <a data-bs-toggle="collapse" class=" nav-link {{ request()->routeIs('admin.properties.index') ? 'active' : ''  }}"
                    href="{{ route('admin.properties.index') }}">
                    <span class="icon_wrapper"><i class="fa-solid fa-building"></i>Properties</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            @else
                <a href="#propertiesSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ request()->routeIs('admin.properties.index') || request()->routeIs('admin.properties.soft_deleted') || request()->routeIs('admin.properties.create') ? 'true' : 'false' }} "
                    class="nav-link collapsed {{ request()->routeIs('admin.properties.index') || request()->routeIs('admin.properties.quick') || request()->routeIs('admin.properties.soft_deleted') || request()->routeIs('admin.properties.create') ? 'active' : '' }}">
                    <span class="icon_wrapper"><i class="fa-solid fa-building"></i>Properties</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
                <ul class="nav-second-level collapse list-unstyled submenu {{ request()->routeIs('admin.properties.index') || request()->routeIs('admin.properties.quick') || request()->routeIs('admin.properties.soft_deleted') || request()->routeIs('admin.properties.create') ? 'show' : '' }}"
                    id="propertiesSubmenu">
                    @can('view properties')
                    @component('components.backend.common.sidebar-sublink')
                        @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.properties.index') ? 'active' : '') }} @endslot
                        @slot('link') {{ route('admin.properties.index') }} @endslot
                        @slot('link_name') View Properties @endslot
                    @endcomponent
                    @endcan
                    @can('create properties')
                    @component('components.backend.common.sidebar-sublink')
                        @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.properties.quick') ? 'active' : '') }} @endslot
                        @slot('link') {{ route('admin.properties.quick') }} @endslot
                        @slot('link_name') Add Property @endslot
                    @endcomponent
                    @endcan
                    @can('view deleted properties')
                    @component('components.backend.common.sidebar-sublink')
                        @slot('class') {{ 'submenu-link' . (request()->routeIs('admin.properties.soft_deleted') ? 'active' : '') }} @endslot
                        @slot('link') {{ route('admin.properties.soft_deleted') }} @endslot
                        @slot('link_name') Deleted Properties @endslot
                    @endcomponent
                    @endcan
                </ul>
            @endif
        </li>
        @endcanany

        @canany(['view contacts', 'create contacts', 'edit contacts', 'delete contacts'])
         <li class="nav-item">
            <a href="{{ route('admin.users.index') }}"
                class="nav-link {{ request()->routeIs('admin.users.index') ? 'active' : ''  }}">
                <span class="icon_wrapper"><i class="fa-solid fa-address-book"></i>Contacts</span>
            </a>
        </li>
        @endcanany

        {{-- Contacts submenu commented out
         <li class="nav-item">
            <a href="#usersSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'true' : 'false' }}"
                class="dropdown-toggle {{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-address-book"></i>Contacts</span>
                <i class="fa fa-angle-down"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled {{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'show' : '' }}"
                id="usersSubmenu">
                @can('view contacts')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('admin.users.index') && !request()->has('role') ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index') }} @endslot
                    @slot('link_name') All @endslot
                @endcomponent
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request('role') === 'Owner' ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index', ['role' => 'Owner']) }} @endslot
                    @slot('link_name') Owners @endslot
                @endcomponent
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request('role') === 'Property Manager' ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index', ['role' => 'Property Manager']) }} @endslot
                    @slot('link_name') Property Managers @endslot
                @endcomponent
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request('role') === 'Tenant' ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index', ['role' => 'Tenant']) }} @endslot
                    @slot('link_name') Tenants @endslot
                @endcomponent
                @endcan
            </ul>
        </li>
        --}}

        {{-- 
         <li class="nav-item">
            <a href="#usersSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'true' : 'false' }}"
                class="dropdown-toggle {{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-address-book"></i>Users</span>
                <i class="fa fa-angle-down"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled {{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'show' : '' }}"
                id="usersSubmenu">
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('admin.users.index') && !request()->has('category') ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index') }} @endslot
                    @slot('link_name') All @endslot
                @endcomponent
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->category == 1 ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index', ['category' => 1]) }} @endslot
                    @slot('link_name') Owners @endslot
                @endcomponent
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->category == 2 ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index', ['category' => 2]) }} @endslot
                    @slot('link_name') Property Managers @endslot
                @endcomponent
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->category == 3 ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index', ['category' => 3]) }} @endslot
                    @slot('link_name') Tenants @endslot
                @endcomponent
                               
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('admin.users.index') }}"
                        class="{{ request()->routeIs('admin.users.index') && !request()->has('category') ? 'active' : '' }}">
                        All
                    </a>
                </li>
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('admin.users.index', ['category' => 1]) }}"
                        class="{{ request()->category == 1 ? 'active' : '' }}">
                        Owners
                    </a>
                </li>
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('admin.users.index', ['category' => 2]) }}"
                        class="{{ request()->category == 2 ? 'active' : '' }}">
                        Property Managers
                    </a>
                </li>
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('admin.users.index', ['category' => 3]) }}"
                        class="{{ request()->category == 3 ? 'active' : '' }}">
                        Tenants
                    </a>
                </li>
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('admin.users.index', ['category' => 4]) }}"
                        class="{{ request()->category == 4 ? 'active' : '' }}">
                        Landlords
                    </a>
                </li>
            </ul>
        </li> 
        --}}

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

        @can('manage tenancies')
         <li class="nav-item">
            <a href="{{ route('admin.tenancies.all') }}"
                class="nav-link {{ request()->routeIs('admin.tenancies.all') ? 'active' : ''  }}">
                <span class="icon_wrapper"><i class="fa-solid fa-home"></i>Tenancies</span>
            </a>
        </li>
        @endcan

        @canany(['view property repair', 'edit property repair', 'create property repair'])
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
                @can('create property repair')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('admin.property_repairs.create') || request()->routeIs('admin.property_repairs.edit') ? 'active submenu-link' : 'submenu-link' }} @endslot
                    @slot('link') {{ route('admin.property_repairs.create') }} @endslot
                    @slot('link_name') Raise Repair Issue @endslot
                @endcomponent
                @endcan
                {{-- <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request()->routeIs('admin.property_repairs.create') || request()->routeIs('admin.property_repairs.edit') ? 'active' : '' }}"
                        href="{{ route('admin.property_repairs.create') }}">
                        Raise Repair Issue
                    </a>
                </li> --}}

                @can('view property repair')
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

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ request()->routeIs('admin.property_repairs.index_tabbed') ? 'active submenu-link' : 'submenu-link' }} @endslot
                            @slot('link') {{ route('admin.property_repairs.index_tabbed') }} @endslot
                            @slot('link_name') Issue List (Tabbed) @endslot
                        @endcomponent

                        {{-- <li class="sidebar-sub-list-item">
                            <a href="{{ route('admin.property_repairs.index') }}"
                                class="{{ request()->fullUrl() === route('admin.property_repairs.index') ? 'active' : '' }}">
                                All
                            </a>
                        </li> --}}


                        @php
                            $statuses = ['Pending', 'Reported', 'Under Process', 'Work Completed', 'Invoice Received', 'Invoice Paid', 'Closed'];
                            $currentStatus = request('status');
                        @endphp

                        @foreach($statuses as $status)
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ $currentStatus === $status ? 'active submenu-link' : 'submenu-link' }} @endslot
                            @slot('link') {{ route('admin.property_repairs.index', ['status' => $status]) }} @endslot
                            @slot('link_name') {{ $status }}
                            @endslot
                        @endcomponent
                            {{-- <li class="sidebar-sub-sub-list-item">
                                <a href="{{ route('admin.property_repairs.index', ['status' => $status]) }}"
                                    class="{{ $currentStatus === $status ? 'active submenu-link' : 'submenu-link' }}">
                                    {{ $status }}
                                </a>
                            </li> --}}
                        @endforeach
                    </ul>
                </li>
                @endcan
            </ul>
        </li>
        @endcanany

        {{-- ── Contractor: only sees Repair Issues ── --}}
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
                    {{-- <li class="sidebar-sub-sub-list-item">
                        <a href="{{ route('admin.invoices.index', ['status' => $key]) }}"
                            class="{{ $currentInvoiceStatus === $key ? 'active' : '' }}">
                            {{ $status }}
                        </a>
                    </li> --}}
                @endforeach
            </ul>
        </li>
        @endcan

        @can('Manage Document Types')
         <li class="nav-item">
            <a href="#" class="nav-link">
                <span class="icon_wrapper"><i class="fa-solid fa-file-alt"></i>Documents</span>
            </a>
        </li>
        @endcan
       
        <!-- Transactions -->
        @canany(['view transactions'])
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
        @endcanany


       <!-- Website Setup -->
        @canany(['manage website setup', 'manage header', 'manage footer', 'manage appearance'])
        <hr>
         <li class="nav-item">
            <a href="#websiteSetupSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ areActiveRoutes(['website.footer', 'website.header', 'website.appearance'], 'true') }}"
                class="nav-link collapsed {{ areActiveRoutes(['website.footer', 'website.header', 'website.appearance']) }}">
                {{-- <i class="las la-desktop aiz-side-nav-icon"></i> --}}
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
                {{-- <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('website.header') }}"
                        class="aiz-side-nav-link {{ areActiveRoutes(['website.header']) }}">
                        <span class="aiz-side-nav-text">Header</span>
                    </a>
                </li>
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('website.footer') }}"
                        class="aiz-side-nav-link {{ areActiveRoutes(['website.footer']) }}">
                        <span class="aiz-side-nav-text">Footer</span>
                    </a>
                </li>
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('website.appearance') }}"
                        class="aiz-side-nav-link {{ areActiveRoutes(['website.appearance']) }}">
                        <span class="aiz-side-nav-text">Appearance</span>
                    </a>
                </li> --}}
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

                <!-- Note Types -->
                @canany(['manage note types'])
                {{-- <li class="sidebar-sub-sub-list-item submenu_wrapper">
                    <a class="{{ areActiveRoutes(['user-categories.index']) }}"
                        href="{{ route('user-categories.index') }}">
                        Categories
                    </a>
                </li>

                <li class="sidebar-sub-sub-list-item submenu_wrapper">
                    <a class="{{ areActiveRoutes(['admin.branches.index']) }}"
                        href="{{ route('admin.branches.index') }}">
                        Branches
                    </a>
                </li>

                <li class="sidebar-sub-sub-list-item submenu_wrapper">
                    <a class="{{ areActiveRoutes(['admin.designations.index']) }}"
                        href="{{ route('admin.designations.index') }}">
                        Designation
                    </a>
                </li> --}}
                <!-- Note Types Section -->
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
                        {{-- <li class="sidebar-sub-sub-list-item">
                            <a class="{{ areActiveRoutes(['admin.note-types.index']) }}"
                                href="{{ route('admin.note-types.index') }}">
                                View All
                            </a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ areActiveRoutes(['admin.note-types.create']) }}"
                                href="{{ route('admin.note-types.create') }}">
                                Add
                            </a>
                        </li> --}}
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
                        {{-- <li class="sidebar-sub-sub-list-item">
                            <a class="{{ areActiveRoutes(['admin.document-types.index']) }}"
                                href="{{ route('admin.document-types.index') }}">
                                View All
                            </a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ areActiveRoutes(['admin.document-types.create']) }}"
                                href="{{ route('admin.document-types.create') }}">
                                Add
                            </a>
                        </li> --}}
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
                        {{-- <li class="sidebar-sub-sub-list-item">
                            <a class="{{ areActiveRoutes(['admin.tenancy_types.index']) }}"
                                href="{{ route('admin.tenancy_types.index') }}">
                                View All
                            </a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ areActiveRoutes(['admin.tenancy_types.create']) }}"
                                href="{{ route('admin.tenancy_types.create') }}">
                                Add
                            </a>
                        </li> --}}
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
                        {{-- <li class="sidebar-sub-sub-list-item">
                            <a class="{{ areActiveRoutes(['admin.job_types.index']) }}"
                                href="{{ route('admin.job_types.index') }}">
                                View All
                            </a>
                        </li> --}}
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

        @unless(auth()->user()->hasRole('Tenant') || auth()->user()->hasRole('Contractor'))
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

                {{-- <li class="sidebar-sub-list-item submenu_wrapper">
                    <a href="#accountingGlSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('backend.accounting.gl_accounts.*') || request()->routeIs('backend.accounting.gl_account_balances.*') || request()->routeIs('backend.accounting.gl_journals.*') || request()->routeIs('backend.accounting.gl_journal_lines.*') ? 'true' : 'false' }}"
                        class="dropdown-toggle {{ request()->routeIs('backend.accounting.gl_accounts.*') || request()->routeIs('backend.accounting.gl_account_balances.*') || request()->routeIs('backend.accounting.gl_journals.*') || request()->routeIs('backend.accounting.gl_journal_lines.*') ? 'active' : '' }}">
                        <span class="icon_wrapper">General Ledger</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ request()->routeIs('backend.accounting.gl_accounts.*') || request()->routeIs('backend.accounting.gl_account_balances.*') || request()->routeIs('backend.accounting.gl_journals.*') || request()->routeIs('backend.accounting.gl_journal_lines.*') ? 'show' : '' }}"
                        id="accountingGlSubmenu">
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.gl_accounts.*') ? 'active' : '' }}" href="{{ route('backend.accounting.gl_accounts.index') }}">GL Accounts</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.gl_account_balances.*') ? 'active' : '' }}" href="{{ route('backend.accounting.gl_account_balances.index') }}">GL Account Balances</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.gl_journals.*') ? 'active' : '' }}" href="{{ route('backend.accounting.gl_journals.index') }}">GL Journals</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.gl_journal_lines.*') ? 'active' : '' }}" href="{{ route('backend.accounting.gl_journal_lines.index') }}">GL Journal Lines</a>
                        </li>
                    </ul>
                </li> --}}

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

                {{-- <li class="sidebar-sub-list-item submenu_wrapper">
                    <a href="#accountingPurchaseSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('backend.accounting.purchase.*') ? 'true' : 'false' }}"
                        class="nav-link collapsed {{ request()->routeIs('backend.accounting.purchase.*') ? 'active' : '' }}">
                        <span class="icon_wrapper">Purchase</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ request()->routeIs('backend.accounting.purchase.*') ? 'show' : '' }}"
                        id="accountingPurchaseSubmenu">
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.purchase.invoices.*') ? 'active' : '' }}" href="{{ route('backend.accounting.purchase.invoices.index') }}">Invoice</a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('backend.accounting.purchase.debit_notes.*') ? 'active' : '' }}" href="{{ route('backend.accounting.purchase.debit_notes.index') }}">Debit Notes</a>
                        </li>
                    </ul>
                </li> --}}

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

                {{-- <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('backend.accounting.bank_reconciliation.*') ? 'active' : '' }}" href="{{ route('backend.accounting.bank_reconciliation.index') }}">
                        <span class="icon_wrapper">Bank Reconciliation</span>
                    </a>
                </li>

                <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('backend.accounting.fixed_assets.*') ? 'active' : '' }}" href="{{ route('backend.accounting.fixed_assets.index') }}">
                        <span class="icon_wrapper">Fixed Assets</span>
                    </a>
                </li> --}}

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

        @hasanyrole('Super Admin|Property Manager')
         <li class="nav-item">
            <a href="#" class="nav-link">
                <span class="icon_wrapper"><i class="fa-solid fa-users"></i>Users</span> 
            </a>
        </li>
         <li class="nav-item">
            <a href="#" class="nav-link">
                <span class="icon_wrapper"><i class="fa-solid fa-cogs"></i>Settings</span> 
            </a>
        </li>
         <li class="nav-item">
            <a href="#" class="nav-link">
                <span class="icon_wrapper"><i class="fa-solid fa-chart-bar"></i>Reports</span> 
            </a>
        </li>
        {{-- removed by Altamash ----  <li class="nav-item">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <a href="#" class="logout_btn_wrapper">
                    <button type="submit" class="logout_btn border-0 background-none">
                        <i class="fa-solid fa-sign-out-alt"></i> Logout
                    </button>
                </a>
            </form>
        </li> --}}
        @endhasanyrole
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
