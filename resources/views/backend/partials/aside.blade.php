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
                <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('admin.properties.index') ? 'active' : '' }}"
                        href="{{ route('admin.properties.index') }}">
                        <img src="{{ asset('asset/images/svg/properties-view.svg') }}" alt="properties-view">
                        View Properties
                    </a>
                </li>
                <!-- <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('admin.properties.create') ? 'active' : '' }}"
                        href="{{ route('admin.properties.create') }}">Quick Add Property</a>
                </li> -->
                <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('admin.properties.quick') ? 'active' : '' }}"
                        href="{{ route('admin.properties.quick') }}">
                        <img src="{{ asset('asset/images/svg/properties-add.svg') }}" alt="properties-add">
                        Add Property
                    </a>
                </li>
                <!-- <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('admin.properties.quick') ? 'active' : '' }}"
                        href="{{ route('admin.properties.quick') }}">Quick Add Property</a>
                </li> -->
                <!-- <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('admin.properties.create') && request()->query('stepform') ? 'active' : '' }}"
                        href="{{ route('admin.properties.create') }}?stepform">Add Property</a>
                </li> -->
                <li class="sidebar-sub-list-item">
                    <a class="{{ request()->routeIs('admin.properties.soft_deleted') ? 'active' : '' }}"
                        href="{{ route('admin.properties.soft_deleted') }}">
                        <img src="{{ asset('asset/images/svg/trash.svg') }}" alt="trash">
                        Deleted Properties
                    </a>
                </li>
            </ul>
        </li>

        <li class="sidebar-list-item">
            <a href="#">
                <img src="{{ asset('asset/images/svg/dashboard.svg') }}" alt="tenancies">
                Tenancies
            </a>
        </li>
        <li class="sidebar-list-item">
            <a href="#">
                <img src="{{ asset('asset/images/svg/owners.svg') }}" alt="owners">
                Owners
            </a>
        </li>
        <li class="sidebar-list-item">
            <a href="#">
                <img src="{{ asset('asset/images/svg/contacts.svg') }}" alt="contacts">
                Contacts
            </a>
        </li>
        <li class="sidebar-list-item">
            <a href="#">
                <img src="{{ asset('asset/images/svg/documents.svg') }}" alt="documents">
                Documents
            </a>
        </li>
        <hr>
        <li class="sidebar-list-item">
            <a href="#">
                <img src="{{ asset('asset/images/svg/users.svg') }}" alt="users">
                Users
            </a>
        </li>
        <li class="sidebar-list-item">
            <a href="#">
                <img src="{{ asset('asset/images/svg/settings.svg') }}" alt="settings">
                Settings
            </a>
        </li>
        <li class="sidebar-list-item">
            <a href="#">
                <img src="{{ asset('asset/images/svg/report.svg') }}" alt="report">
                Reports
            </a>
        </li>
        <li class="sidebar-list-item">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-link logout_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
                    </svg>
                  Logout</button>
            </form>
        </li>
    </ul>
</aside>