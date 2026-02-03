<aside id="menu" class="sidebar bg-light sidebar">

    {{--
    <div class="sidebar-header">
        <h4>Admin Menu</h4>
    </div> --}}
    <ul class="list-unstyled components">
        <li class="sidebar-list-item">
            <a class="{{ request()->routeIs('backend.dashboard') ? 'active' : '' }}"
                href="{{ route('backend.dashboard') }}">
                <img src="{{ asset('asset/images/svg/dashboard.svg') }}" alt="dashboard">
                Dashboard
            </a>
        </li>
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#propertiesSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.properties.index') || request()->routeIs('admin.properties.create') ? 'true' : 'false' }}"
                class="dropdown-toggle {{ request()->routeIs('admin.properties.index') || request()->routeIs('admin.properties.quick') || request()->routeIs('admin.properties.create') ? 'active' : '' }}">
                <img src="{{ asset('asset/images/svg/properties.svg') }}" alt="properties">
                Properties
            </a>
            <ul class="nav-second-level collapse list-unstyled {{ request()->routeIs('admin.properties.index') || request()->routeIs('admin.properties.quick') || request()->routeIs('admin.properties.create') ? 'show' : '' }}"
                id="propertiesSubmenu">
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request()->routeIs('admin.properties.index') ? 'active' : '' }}"
                        href="{{ route('admin.properties.index') }}">
                        <img src="{{ asset('asset/images/svg/properties-view.svg') }}" alt="properties-view">
                        View Properties
                    </a>
                </li>
                <!-- <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request()->routeIs('admin.properties.create') ? 'active' : '' }}"
                        href="{{ route('admin.properties.create') }}">Quick Add Property</a>
                </li> -->
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request()->routeIs('admin.properties.quick') ? 'active' : '' }}"
                        href="{{ route('admin.properties.quick') }}">
                        <img src="{{ asset('asset/images/svg/properties-add.svg') }}" alt="properties-add">
                        Add Property
                    </a>
                </li>
                <!-- <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request()->routeIs('admin.properties.quick') ? 'active' : '' }}"
                        href="{{ route('admin.properties.quick') }}">Quick Add Property</a>
                </li> -->
                <!-- <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request()->routeIs('admin.properties.create') && request()->query('stepform') ? 'active' : '' }}"
                        href="{{ route('admin.properties.create') }}?stepform">Add Property</a>
                </li> -->
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a class="{{ request()->routeIs('admin.properties.soft_deleted') ? 'active' : '' }}"
                        href="{{ route('admin.properties.soft_deleted') }}">
                        <img src="{{ asset('asset/images/svg/trash.svg') }}" alt="trash">
                        Deleted Properties
                    </a>
                </li>
            </ul>
        </li>

        <li class="sidebar-list-item submenu_wrapper">
            <a href="#usersSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'true' : 'false' }}"
                class="dropdown-toggle {{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'active' : '' }}">
                <img src="{{ asset('asset/images/svg/dashboard.svg') }}" alt="users">
                Users
            </a>
            <ul class="nav-second-level collapse list-unstyled {{ request()->routeIs('admin.users.index') || request()->routeIs('users.create') ? 'show' : '' }}"
                id="usersSubmenu">
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('admin.users.index') }}"
                        class="{{ request()->routeIs('admin.users.index') && !request()->has('category') ? 'active' : '' }}">
                        All
                    </a>
                </li>
                <li class="sidebar-sub-list-item py-0 mb-0">
                    <a href="{{ route('admin.users.index', ['category' => 1]) }}"
                        class="{{ request()->category == 1 ? 'active' : '' }}">
                        <img src="{{ asset('asset/images/svg/owners.svg') }}" alt="owners">
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


        <li class="sidebar-list-item submenu_wrapper">
            <a href="#masterManageSubmenu" data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('admin.branches.index') || request()->routeIs('admin.designations.index') || request()->routeIs('admin.tenancy_types.index') || request()->routeIs('admin.tenancy_types.create') || request()->routeIs('admin.tenancy_sub_statuses.index') || request()->routeIs('admin.tenancy_sub_statuses.create') ? 'true' : 'false' }}"
                class="dropdown-toggle {{ request()->routeIs('admin.branches.index') || request()->routeIs('admin.designations.index') || request()->routeIs('admin.tenancy_types.index') || request()->routeIs('admin.tenancy_types.create') || request()->routeIs('admin.tenancy_sub_statuses.index') || request()->routeIs('admin.tenancy_sub_statuses.create') ? 'active' : '' }}">
                <img src="{{ asset('asset/images/svg/dashboard.svg') }}" alt="tenancies">
                {{-- <img src="{{ asset('asset/images/svg/master-manage.svg') }}" alt="master-manage"> --}}
                Master Manage
            </a>
            <ul class="nav-second-level collapse list-unstyled {{ request()->routeIs('user-categories.index') || request()->routeIs('admin.branches.index') || request()->routeIs('admin.designations.index') || request()->routeIs('admin.tenancy_types.index') || request()->routeIs('admin.tenancy_types.create') || request()->routeIs('admin.tenancy_sub_statuses.index') || request()->routeIs('admin.tenancy_sub_statuses.create') ? 'show' : '' }}"
                id="masterManageSubmenu">

                <li class="sidebar-list-item submenu_wrapper ">
                    <a class="{{ request()->routeIs('user-categories.index') ? 'active' : '' }}"
                        href="{{ route('user-categories.index') }}">
                        <img src="{{ asset('asset/images/svg/users.svg') }}" alt="users">
                        Categories
                    </a>
                </li>

                <li class="sidebar-list-item submenu_wrapper">
                    <a class="{{ request()->routeIs('admin.branches.index') ? 'active' : '' }}"
                        href="{{ route('admin.branches.index') }}">
                        <img src="{{ asset('asset/images/svg/users.svg') }}" alt="branches">
                        Branches
                    </a>
                </li>

                <li class="sidebar-list-item submenu_wrapper">
                    <a class="{{ request()->routeIs('admin.designations.index') ? 'active' : '' }}"
                        href="{{ route('admin.designations.index') }}">
                        <img src="{{ asset('asset/images/svg/users.svg') }}" alt="branches">
                        Designation
                    </a>
                </li>

                <!-- Tenancy Types Section -->
                <li class="sidebar-sub-list-item py-0 mb-0 submenu_wrapper">
                    <a href="#tenancyTypesSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('admin.tenancy_types.index') || request()->routeIs('admin.tenancy_types.create') ? 'true' : 'false' }}"
                        class="dropdown-toggle {{ request()->routeIs('admin.tenancy_types.index') || request()->routeIs('admin.tenancy_types.create') ? 'active' : '' }}">
                        {{-- <img src="{{ asset('asset/images/svg/tenancy-type.svg') }}" alt="tenancy-type"> --}}
                        Tenancy Types
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ request()->routeIs('admin.tenancy_types.index') || request()->routeIs('admin.tenancy_types.create') ? 'show' : '' }}"
                        id="tenancyTypesSubmenu">
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('admin.tenancy_types.index') ? 'active' : '' }}"
                                href="{{ route('admin.tenancy_types.index') }}">
                                {{-- <img src="{{ asset('asset/images/svg/tenancy-view.svg') }}" alt="view-tenancy-type"> --}}
                                View All
                            </a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('admin.tenancy_types.create') ? 'active' : '' }}"
                                href="{{ route('admin.tenancy_types.create') }}">
                                {{-- <img src="{{ asset('asset/images/svg/tenancy-add.svg') }}" alt="add-tenancy-type"> --}}
                                Add
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Tenancy Sub Status Section -->
                <li class="sidebar-sub-list-item py-0 mb-0 submenu_wrapper">
                    <a href="#tenancySubStatusSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('admin.tenancy_sub_statuses.index') || request()->routeIs('admin.tenancy_sub_statuses.create') ? 'true' : 'false' }}"
                        class="dropdown-toggle {{ request()->routeIs('admin.tenancy_sub_statuses.index') || request()->routeIs('admin.tenancy_sub_statuses.create') ? 'active' : '' }}">
                        {{-- <img src="{{ asset('asset/images/svg/tenancy-sub-status.svg') }}" alt="tenancy-sub-status"> --}}
                        Tenancy Sub Status
                    </a>
                    <ul class="nav-third-level collapse list-unstyled {{ request()->routeIs('admin.tenancy_sub_statuses.index') || request()->routeIs('admin.tenancy_sub_statuses.create') ? 'show' : '' }}"
                        id="tenancySubStatusSubmenu">
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('admin.tenancy_sub_statuses.index') ? 'active' : '' }}"
                                href="{{ route('admin.tenancy_sub_statuses.index') }}">
                                {{-- <img src="{{ asset('asset/images/svg/tenancy-sub-status-view.svg') }}" alt="view-tenancy-sub-status"> --}}
                                View All
                            </a>
                        </li>
                        <li class="sidebar-sub-sub-list-item">
                            <a class="{{ request()->routeIs('admin.tenancy_sub_statuses.create') ? 'active' : '' }}"
                                href="{{ route('admin.tenancy_sub_statuses.create') }}">
                                {{-- <img src="{{ asset('asset/images/svg/tenancy-sub-status-add.svg') }}" alt="add-tenancy-sub-status"> --}}
                                Add
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </li>

        <li class="sidebar-list-item submenu_wrapper">
            <a href="#">
                <img src="{{ asset('asset/images/svg/dashboard.svg') }}" alt="tenancies">
                Tenancies
            </a>
        </li>

        <li class="sidebar-list-item submenu_wrapper">
            <a href="#">
                <img src="{{ asset('asset/images/svg/documents.svg') }}" alt="documents">
                Documents
            </a>
        </li>
        <hr>
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#">
                <img src="{{ asset('asset/images/svg/users.svg') }}" alt="users">
                Users
            </a>
        </li>
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#">
                <img src="{{ asset('asset/images/svg/settings.svg') }}" alt="settings">
                Settings
            </a>
        </li>
        <li class="sidebar-list-item submenu_wrapper">
            <a href="#">
                <img src="{{ asset('asset/images/svg/report.svg') }}" alt="report">
                Reports
            </a>
        </li>
        <li class="sidebar-list-item submenu_wrapper">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <a href="#">
                <button type="submit" class="logout_btn border-0 background-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
                        <path fill-rule="evenodd"
                            d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
                    </svg>
                    Logout</button>
                    </a>
            </form>
        </li>
    </ul>
</aside>
<div class="backdrop"></div>
