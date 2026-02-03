@php
    $roles = auth()->user()->getRoleNames();
@endphp
<aside id="menu" class="sidebar bg-light sidebar">
    <div class="dropdown position-relative">
        <button class="p-0 btn btn-light dropdown-toggle d-flex align-items-center gap-2 user-dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            @if(auth()->user()->profile_picture)
                <img src="{{ asset('storage/' . auth()->user()->profile_picture) }}" alt="Profile" class="rounded-circle profile-img" />
            @else
                <div class="bg-secondary rounded-circle d-flex justify-content-center align-items-center default-profile-icon">
                    <i class="fa-solid fa-user text-white"></i>
                </div>
            @endif

            <div class="flex-grow-1 text-start user-info">
                <h6 class="mb-0 text-truncate" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</h6>
                <small title="{{ auth()->user()->email }}" class="text-muted d-block text-truncate">{{ auth()->user()->email }}</small>
                <small title="{{ $roles->implode(', ') }}" class="text-muted role-text">{{ $roles->count() === 1 ? 'Role' : 'Roles' }}: {{ $roles->implode(', ') }}</small>
            </div>
        </button>

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

    <div class="pt-3 px-3">
        <div class="input-group mb-2">
            <input type="text" id="menu-search" placeholder="Search menu..." class="form-control">
            <button id="reset-search" class="btn btn-outline-secondary" type="button">&times;</button>
        </div>
    </div>

    <ul class="list-unstyled components">
        <li class="sidebar-list-item submenu_wrapper">
            <a class="{{ request()->routeIs('backend.dashboard') ? 'active' : '' }}"
                href="{{ route('backend.dashboard') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-tachometer-alt"></i>Dashboard</span>
            </a>
        </li>

        @can('view calendar')
        {{-- Calendar --}}
        <li class="sidebar-list-item submenu_wrapper">
            <a class="{{ request()->routeIs('backend.events.calendar') ? 'active' : '' }}"
                href="{{ route('backend.events.calendar') }}">
                <span class="icon_wrapper"><i class="fa-solid fa-calendar-check"></i>Calendar</span>
            </a>
        </li>
        @endcan
        
        @canany(['view properties', 'edit properties', 'create properties'])
        {{-- Users --}}
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#propertiesSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.properties.index') || request()->routeIs('admin.properties.soft_deleted') || request()->routeIs('admin.properties.create') ? 'true' : 'false' }} "
                class="dropdown-toggle {{ request()->routeIs('admin.properties.index') || request()->routeIs('admin.properties.quick') || request()->routeIs('admin.properties.soft_deleted') || request()->routeIs('admin.properties.create') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-building"></i>Properties</span>
                <i class="fa fa-angle-down"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled {{ request()->routeIs('admin.properties.index') || request()->routeIs('admin.properties.quick') || request()->routeIs('admin.properties.soft_deleted') || request()->routeIs('admin.properties.create') ? 'show' : '' }}"
                id="propertiesSubmenu">
                @can('view properties')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('admin.properties.index') ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.properties.index') }} @endslot
                    @slot('link_name') View Properties @endslot
                @endcomponent
                @endcan
                @can('create properties')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('admin.properties.quick') ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.properties.quick') }} @endslot
                    @slot('link_name') Add Property @endslot
                @endcomponent
                @endcan
                @can('view deleted properties')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('admin.properties.soft_deleted') ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.properties.soft_deleted') }} @endslot
                    @slot('link_name') Deleted Properties @endslot
                @endcomponent
                @endcan
                {{-- <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request
                {{-- <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request()->routeIs('admin.properties.index') ? 'active' : '' }}"
                        href="{{ route('admin.properties.index') }}">
                        View Properties
                    </a>
                </li>
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request()->routeIs('admin.properties.quick') ? 'active' : '' }}"
                        href="{{ route('admin.properties.quick') }}">
                        Add Property
                    </a>
                </li>
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request()->routeIs('admin.properties.soft_deleted') ? 'active' : '' }}"
                        href="{{ route('admin.properties.soft_deleted') }}">
                        Deleted Properties
                    </a>
                </li> --}}
            </ul>
        </li>
        @endcanany

        @canany(['View Contacts', 'Create Contacts', 'Edit Contacts', 'Delete Contacts'])
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#usersSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'true' : 'false' }}"
                class="dropdown-toggle {{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-address-book"></i>Contacts</span>
                <i class="fa fa-angle-down"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled {{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'show' : '' }}"
                id="usersSubmenu">
                
                @can('View Contacts')
                {{-- All Users --}}
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('admin.users.index') && !request()->has('role') ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index') }} @endslot
                    @slot('link_name') All @endslot
                @endcomponent

                {{-- Owners --}}
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request('role') === 'Owner' ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index', ['role' => 'Owner']) }} @endslot
                    @slot('link_name') Owners @endslot
                @endcomponent

                {{-- Property Managers --}}
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request('role') === 'Property Manager' ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index', ['role' => 'Property Manager']) }} @endslot
                    @slot('link_name') Property Managers @endslot
                @endcomponent

                {{-- Tenants --}}
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request('role') === 'Tenant' ? 'active' : '' }} @endslot
                    @slot('link') {{ route('admin.users.index', ['role' => 'Tenant']) }} @endslot
                    @slot('link_name') Tenants @endslot
                @endcomponent
                @endcan
            </ul>
        </li>
        @endcanany

        {{-- 
        <li class="sidebar-list-item submenu_wrapper">
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

        @can('manage tenancies')
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#">
                <span class="icon_wrapper"><i class="fa-solid fa-home"></i>Tenancies</span>
            </a>
        </li>
        @endcan

        @canany(['view property repair', 'edit property repair', 'create property repair'])
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#repairSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.property_repairs.*') ? 'true' : 'false' }}"
                class="dropdown-toggle {{ request()->routeIs('admin.property_repairs.*') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fa-solid fa-building"></i>Repair</span>
                <i class="fa fa-angle-down"></i>
            </a>
            <ul class="nav-second-level collapse list-unstyled {{ request()->routeIs('admin.property_repairs.*') ? 'show' : '' }}"
                id="repairSubmenu">
                <!-- Raise Repair Issue -->
                @can('create property repair')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ request()->routeIs('admin.property_repairs.create') || request()->routeIs('admin.property_repairs.edit') ? 'active' : '' }} @endslot
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
                <li class="sidebar-sub-list-item py-0 mb-0 submenu_wrapper">
                    <a href="#repairIssuesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('admin.property_repairs.index') ? 'true' : 'false' }}"
                        class="dropdown-toggle {{ request()->routeIs('admin.property_repairs.index') || request()->routeIs('admin.property_repairs.show') ? 'active' : '' }}">
                        <span class="icon_wrapper">Repair Issues</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ request()->routeIs('admin.property_repairs.index') ? 'show' : '' }}"
                        id="repairIssuesSubmenu">

                        <!-- "All" Status Option -->
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ request()->fullUrl() === route('admin.property_repairs.index') ? 'active' : '' }} @endslot
                            @slot('link') {{ route('admin.property_repairs.index') }} @endslot
                            @slot('link_name') All @endslot
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
                            @slot('class') {{ $currentStatus === $status ? 'active' : '' }} @endslot
                            @slot('link') {{ route('admin.property_repairs.index', ['status' => $status]) }} @endslot
                            @slot('link_name') {{ $status }}
                            @endslot
                        @endcomponent
                            {{-- <li class="sidebar-sub-sub-list-item">
                                <a href="{{ route('admin.property_repairs.index', ['status' => $status]) }}"
                                    class="{{ $currentStatus === $status ? 'active' : '' }}">
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

        <li class="sidebar-list-item submenu_wrapper">
            <a href="#invoiceSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.invoices.index') ? 'true' : 'false' }}"
                class="dropdown-toggle {{ request()->routeIs('admin.invoices.index') ? 'active' : '' }}">
                <span class="icon_wrapper"><i class="fas fa-file-invoice-dollar aiz-side-nav-icon"></i>Invoices</span>
                <i class="fa fa-angle-down"></i>
            </a>
            <ul class="nav-second-level list-unstyled collapse {{ request()->routeIs('admin.invoices.index') ? 'show' : '' }}"
                id="invoiceSubmenu">
                @foreach($invoiceStatuses as $key => $status)
                    @component('components.backend.common.sidebar-sublink')
                        @slot('class') {{ $currentInvoiceStatus === $key ? 'active' : '' }} @endslot
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
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#">
                <span class="icon_wrapper"><i class="fa-solid fa-file-alt"></i>Documents</span>
            </a>
        </li>
        @endcan
       
        <!-- Transactions -->
        @canany(['view transactions'])
            <li class="sidebar-list-item submenu_wrapper">
                <a href="#transactionsSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['backend.transactions.index'], 'true') }}"
                    class="dropdown-toggle {{ areActiveRoutes(['backend.transactions.index']) }}">
                    <span class="icon_wrapper pb_25">
                        <i class="fa-solid fa-money-bill-transfer"></i> Transactions
                    </span>
                    <i class="fa fa-angle-down"></i>
                </a>

                <ul class="nav-second-level list-unstyled collapse {{ areActiveRoutes(['backend.transactions.index'], 'show') }}"
                    id="transactionsSubmenu">

                    @can('view transactions')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['backend.transactions.index']) }} @endslot
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
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#websiteSetupSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ areActiveRoutes(['website.footer', 'website.header', 'website.appearance'], 'true') }}"
                class="dropdown-toggle {{ areActiveRoutes(['website.footer', 'website.header', 'website.appearance']) }}">
                {{-- <i class="las la-desktop aiz-side-nav-icon"></i> --}}
                <span class="icon_wrapper pb_25"><i class="fa-solid fa-cog"></i>Website Setup</span>
                <i class="fa fa-angle-down"></i>
            </a>
            <ul class="nav-second-level list-unstyled collapse {{ areActiveRoutes(['website.footer', 'website.header', 'website.appearance'], 'show') }}"
                id="websiteSetupSubmenu">
                @can('manage header')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ areActiveRoutes(['website.header']) }} @endslot
                    @slot('link') {{ route('website.header') }} @endslot
                    @slot('link_name') Header
                    @endslot
                @endcomponent
                @endcan
                @can('manage footer')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ areActiveRoutes(['website.footer']) }} @endslot
                    @slot('link') {{ route('website.footer') }} @endslot
                    @slot('link_name') Footer
                    @endslot
                @endcomponent        
                @endcan
                @can('manage appearance')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ areActiveRoutes(['website.appearance']) }} @endslot
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

        <li class="sidebar-list-item submenu_wrapper">
            <a href="#masterManageSubmenu" data-bs-toggle="collapse" aria-expanded="{{ areActiveRoutes($masterManageRoutes, 'true') }}" class="dropdown-toggle {{ areActiveRoutes($masterManageRoutes) }}">
                <span class="icon_wrapper pb_25"><i class="fa-solid fa-cogs"></i>Master Manage</span>
                <i class="fa fa-angle-down"></i>
            </a>
            <ul class="nav-second-level list-unstyled collapse {{ areActiveRoutes($masterManageRoutes, 'show') }}" id="masterManageSubmenu">

                @can('manage categories')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ areActiveRoutes(['user-categories.index']) }} @endslot
                    @slot('link') {{ route('user-categories.index') }} @endslot
                    @slot('link_name') Categories
                    @endslot
                @endcomponent
                @endcan

                @can('manage branches')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ areActiveRoutes(['admin.branches.index']) }} @endslot
                    @slot('link') {{ route('admin.branches.index') }} @endslot
                    @slot('link_name') Branches
                    @endslot
                @endcomponent
                @endcan

                @can('manage designations')
                @component('components.backend.common.sidebar-sublink')
                    @slot('class') {{ areActiveRoutes(['admin.designations.index']) }} @endslot
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
                <li class="sidebar-sub-list-item submenu_wrapper">
                    <a href="#noteTypesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ areActiveRoutes(['admin.note-types.index', 'admin.note-types.create'], 'true') }}"
                        class="dropdown-toggle {{ areActiveRoutes(['admin.note-types.index', 'admin.note-types.create']) }}">
                        
                        <span class="icon_wrapper">Note Types</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ areActiveRoutes(['admin.note-types.index', 'admin.note-types.create'], 'show') }}"
                        id="noteTypesSubmenu">
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['admin.note-types.index']) }} @endslot
                            @slot('link') {{ route('admin.note-types.index') }} @endslot
                            @slot('link_name') View All
                            @endslot
                        @endcomponent
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['admin.note-types.create']) }} @endslot
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
                <li class="sidebar-sub-list-item submenu_wrapper">
                    <a href="#documentTypesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ areActiveRoutes(['admin.document-types.index', 'admin.document-types.create'], 'true') }}"
                        class="dropdown-toggle {{ areActiveRoutes(['admin.document-types.index', 'admin.document-types.create']) }}">
                        
                        <span class="icon_wrapper">Document Types</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ areActiveRoutes(['admin.document-types.index', 'admin.document-types.create'], 'show') }}"
                        id="documentTypesSubmenu">
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['admin.document-types.index']) }} @endslot
                            @slot('link') {{ route('admin.document-types.index') }} @endslot
                            @slot('link_name') View All
                            @endslot
                        @endcomponent
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['admin.document-types.create']) }} @endslot
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
                <li class="sidebar-sub-list-item submenu_wrapper">
                    <a href="#tenancyTypesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ areActiveRoutes(['admin.tenancy_types.index', 'admin.tenancy_types.create'], 'true') }}"
                        class="dropdown-toggle {{ areActiveRoutes(['admin.tenancy_types.index', 'admin.tenancy_types.create']) }}">

                        <span class="icon_wrapper">Tenancy Types</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ areActiveRoutes(['admin.tenancy_types.index', 'admin.tenancy_types.create'], 'show') }}"
                        id="tenancyTypesSubmenu">
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['admin.tenancy_types.index']) }} @endslot
                            @slot('link') {{ route('admin.tenancy_types.index') }} @endslot
                            @slot('link_name') View All
                            @endslot
                        @endcomponent
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['admin.tenancy_types.create']) }} @endslot
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
                <li class="sidebar-sub-list-item submenu_wrapper">
                    <a href="#tenancySubStatusSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ areActiveRoutes(['admin.tenancy_sub_statuses.index', 'admin.tenancy_sub_statuses.create'], 'true') }}"
                        class="dropdown-toggle {{ areActiveRoutes(['admin.tenancy_sub_statuses.index', 'admin.tenancy_sub_statuses.create']) }}">
                        
                        <span class="icon_wrapper">Tenancy Sub Status</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ areActiveRoutes(['admin.tenancy_sub_statuses.index', 'admin.tenancy_sub_statuses.create'], 'show') }}"
                        id="tenancySubStatusSubmenu">
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['admin.tenancy_sub_statuses.index']) }} @endslot
                            @slot('link') {{ route('admin.tenancy_sub_statuses.index') }} @endslot
                            @slot('link_name') View All
                            @endslot
                        @endcomponent
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['admin.tenancy_sub_statuses.create']) }} @endslot
                            @slot('link') {{ route('admin.tenancy_sub_statuses.create') }} @endslot
                            @slot('link_name') Add
                            @endslot
                        @endcomponent
                    </ul>
                </li>
                @endcan

                <!-- Event Type Section -->
                @can('manage event types')
                <li class="sidebar-sub-list-item submenu_wrapper">
                    <a href="#eventTypeSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['backend.event_types.index', 'backend.event_types.create'], 'true') }}"
                    class="dropdown-toggle {{ areActiveRoutes(['backend.event_types.index', 'backend.event_types.create']) }}">
                        
                        <span class="icon_wrapper">Event Type</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ areActiveRoutes(['backend.event_types.index', 'backend.event_types.create'], 'show') }}"
                        id="eventTypeSubmenu">

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['backend.event_types.index']) }} @endslot
                            @slot('link') {{ route('backend.event_types.index') }} @endslot
                            @slot('link_name') View All @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['backend.event_types.create']) }} @endslot
                            @slot('link') {{ route('backend.event_types.create') }} @endslot
                            @slot('link_name') Add @endslot
                        @endcomponent

                    </ul>
                </li>
                @endcan

                <!-- Event Sub Type Section -->
                @can('manage event sub types')
                <li class="sidebar-sub-list-item submenu_wrapper">
                    <a href="#eventSubTypeSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['backend.event_sub_types.index', 'backend.event_sub_types.create'], 'true') }}"
                    class="dropdown-toggle {{ areActiveRoutes(['backend.event_sub_types.index', 'backend.event_sub_types.create']) }}">

                        <span class="icon_wrapper">Event Sub Type</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ areActiveRoutes(['backend.event_sub_types.index', 'backend.event_sub_types.create'], 'show') }}"
                        id="eventSubTypeSubmenu">

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['backend.event_sub_types.index']) }} @endslot
                            @slot('link') {{ route('backend.event_sub_types.index') }} @endslot
                            @slot('link_name') View All @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['backend.event_sub_types.create']) }} @endslot
                            @slot('link') {{ route('backend.event_sub_types.create') }} @endslot
                            @slot('link_name') Add @endslot
                        @endcomponent

                    </ul>
                </li>
                @endcan

                <!-- Job Types Section -->
                @can('manage job types')
                <li class="sidebar-sub-list-item  submenu_wrapper">
                    <a href="#jobTypesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ areActiveRoutes(['admin.job_types.index', 'admin.job_types.create'], 'true') }}"
                        class="dropdown-toggle {{ areActiveRoutes(['admin.job_types.index', 'admin.job_types.create']) }}">
                        
                        <span class="icon_wrapper">Job Types</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ areActiveRoutes(['admin.job_types.index', 'admin.job_types.create'], 'show') }}"
                        id="jobTypesSubmenu">
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['admin.job_types.index']) }} @endslot
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
                    <li class="sidebar-sub-list-item submenu_wrapper">
                        <a href="#transactionCategoriesSubmenu" data-bs-toggle="collapse"
                            aria-expanded="{{ areActiveRoutes(['backend.transaction_categories.index', 'backend.transaction_categories.create'], 'true') }}"
                            class="dropdown-toggle {{ areActiveRoutes(['backend.transaction_categories.index', 'backend.transaction_categories.create']) }}">
                            
                            <span class="icon_wrapper">Transaction Categories</span>
                            <i class="fa fa-angle-down"></i>
                        </a>
                        <ul class="nav-third-level collapse list-unstyled {{ areActiveRoutes(['backend.transaction_categories.index', 'backend.transaction_categories.create'], 'show') }}"
                            id="transactionCategoriesSubmenu">

                            @component('components.backend.common.sidebar-sublink')
                                @slot('class') {{ areActiveRoutes(['backend.transaction_categories.index']) }} @endslot
                                @slot('link') {{ route('backend.transaction_categories.index') }} @endslot
                                @slot('link_name') View All @endslot
                            @endcomponent

                            @component('components.backend.common.sidebar-sublink')
                                @slot('class') {{ areActiveRoutes(['backend.transaction_categories.create']) }} @endslot
                                @slot('link') {{ route('backend.transaction_categories.create') }} @endslot
                                @slot('link_name') Add @endslot
                            @endcomponent
                        </ul>
                    </li>
                @endcan
                
            </ul>
        </li>
        @endcanany

        <!-- Staffs -->
        @canany(['view all staffs', 'view staff roles'])
            <li class="sidebar-list-item submenu_wrapper">
                <a href="#staffsSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['staffs.index', 'staffs.create', 'staffs.edit', 'roles.index', 'roles.create', 'roles.edit'], 'true') }}"
                    class="dropdown-toggle {{ areActiveRoutes(['staffs.index', 'staffs.create', 'staffs.edit', 'roles.index', 'roles.create', 'roles.edit']) }}">
                    <span class="icon_wrapper pb_25">
                        <i class="fa-solid fa-users"></i> Staffs
                    </span>
                    <i class="fa fa-angle-down"></i>
                </a>

                <ul class="nav-second-level list-unstyled collapse {{ areActiveRoutes(['staffs.index', 'staffs.create', 'staffs.edit', 'roles.index', 'roles.create', 'roles.edit'], 'show') }}"
                    id="staffsSubmenu">

                    @can('view all staffs')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['staffs.index', 'staffs.create', 'staffs.edit']) }} @endslot
                            @slot('link') {{ route('staffs.index') }} @endslot
                            @slot('link_name') All staffs @endslot
                        @endcomponent
                    @endcan

                    @can('view staff roles')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['roles.index', 'roles.create', 'roles.edit']) }} @endslot
                            @slot('link') {{ route('roles.index') }} @endslot
                            @slot('link_name') Staff permissions @endslot
                        @endcomponent
                    @endcan

                </ul>
            </li>
        @endcanany
        <!-- Setup & Configurations -->
        @canany(['view smtp settings'])
            <li class="sidebar-list-item submenu_wrapper">
                <a href="#setupConfigurationsSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['smtp_settings.index'], 'true') }}"
                    class="dropdown-toggle {{ areActiveRoutes(['smtp_settings.index']) }}">
                    <span class="icon_wrapper pb_25">
                        <i class="fa-solid fa-sliders"></i> Setup & Configurations
                    </span>
                    <i class="fa fa-angle-down"></i>
                </a>

                <ul class="nav-second-level list-unstyled collapse {{ areActiveRoutes(['smtp_settings.index'], 'show') }}"
                    id="setupConfigurationsSubmenu">

                    @can('view smtp settings')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['smtp_settings.index']) }} @endslot
                            @slot('link') {{ route('smtp_settings.index') }} @endslot
                            @slot('link_name') SMTP Settings @endslot
                        @endcomponent
                    @endcan

                    {{-- Add more settings here if needed --}}
                </ul>
            </li>
        @endcanany

        <!-- Account Headers -->
        @canany(['view account headers'])
            <li class="sidebar-list-item submenu_wrapper">
                <a href="#accountHeadersSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['backend.account_headers.index'], 'true') }}"
                    class="dropdown-toggle {{ areActiveRoutes(['backend.account_headers.index']) }}">
                    <span class="icon_wrapper pb_25">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Account Headers
                    </span>
                    <i class="fa fa-angle-down"></i>
                </a>

                <ul class="nav-second-level list-unstyled collapse {{ areActiveRoutes(['backend.account_headers.index'], 'show') }}"
                    id="accountHeadersSubmenu">

                    @can('view account headers')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ areActiveRoutes(['backend.account_headers.index']) }} @endslot
                            @slot('link') {{ route('backend.account_headers.index') }} @endslot
                            @slot('link_name') Account Headers @endslot
                        @endcomponent
                    @endcan

                </ul>
            </li>
        @endcanany


        <!-- marketing -->
        @canany(['manage email templates'])
            <li class="sidebar-list-item submenu_wrapper">
                <a href="#emailTemplatesSubmenu" data-bs-toggle="collapse"
                    aria-expanded="{{ areActiveRoutes(['email_templates.index'], 'true') }}"
                    class="dropdown-toggle {{ areActiveRoutes(['email_templates.index']) }}">
                    <span class="icon_wrapper pb_25">
                        <i class="fa-solid fa-envelope"></i> Email Templates
                    </span>
                    <i class="fa fa-angle-down"></i>
                </a>

                <ul class="nav-second-level list-unstyled collapse {{ areActiveRoutes(['email_templates.index'], 'show') }}"
                    id="emailTemplatesSubmenu">

                    @can('manage email templates')
                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ request()->is('email-templates*') ? 'active' : '' }} @endslot
                            @slot('link') {{ route('email-templates.index', 'all') }} @endslot
                            @slot('link_name') Common Templates @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ request()->is('email-templates*') ? 'active' : '' }} @endslot
                            @slot('link') {{ route('email-templates.index', 'admin') }} @endslot
                            @slot('link_name') Admin Templates @endslot
                        @endcomponent                    

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ request()->is('email-templates*') ? 'active' : '' }} @endslot
                            @slot('link') {{ route('email-templates.index', 'agent') }} @endslot
                            @slot('link_name') Agent Templates @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ request()->is('email-templates*') ? 'active' : '' }} @endslot
                            @slot('link') {{ route('email-templates.index', 'contractor') }} @endslot
                            @slot('link_name') Contractor Templates @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ request()->is('email-templates*') ? 'active' : '' }} @endslot
                            @slot('link') {{ route('email-templates.index', 'owner') }} @endslot
                            @slot('link_name') Owner Templates @endslot
                        @endcomponent

                        @component('components.backend.common.sidebar-sublink')
                            @slot('class') {{ request()->is('email-templates*') ? 'active' : '' }} @endslot
                            @slot('link') {{ route('email-templates.index', 'tenant') }} @endslot
                            @slot('link_name') Tenant Templates @endslot
                        @endcomponent
                    @endcan

                </ul>
            </li>
        @endcanany


        {{-- @if(!auth()->user()->hasAnyRole(['Super Admin', 'Property Manager']))
            <li class="sidebar-list-item submenu_wrapper">
                <a href="{{ route('user.profile') }}">
                    <span class="icon_wrapper"><i class="fa-solid fa-user"></i>Profile</span>
                </a>
            </li>
        @endif --}}

        @hasanyrole('Super Admin|Property Manager')
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#">
                <span class="icon_wrapper"><i class="fa-solid fa-users"></i>Users</span> 
            </a>
        </li>
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#">
                <span class="icon_wrapper"><i class="fa-solid fa-cogs"></i>Settings</span> 
            </a>
        </li>
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#">
                <span class="icon_wrapper"><i class="fa-solid fa-chart-bar"></i>Reports</span> 
            </a>
        </li>
        <li class="sidebar-list-item submenu_wrapper">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <a href="#" class="logout_btn_wrapper">
                    <button type="submit" class="logout_btn border-0 background-none">
                        <i class="fa-solid fa-sign-out-alt"></i> Logout
                    </button>
                </a>
            </form>
        </li>
        @endhasanyrole
    </ul>
</aside>
<div class="backdrop"></div>