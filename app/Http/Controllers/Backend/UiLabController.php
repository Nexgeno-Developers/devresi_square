<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UiLabController extends Controller
{
    public function index(Request $request): View
    {
        // Launch Step 5 / 44: experimental UI lab is Super Admin only — never a customer product.
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $screens = [
            'login' => ['view' => 'login', 'title' => 'Login', 'group' => 'Start here'],
            'dashboard' => ['view' => 'dashboard', 'title' => 'Home', 'group' => 'Landlord'],
            'dashboard-empty' => ['view' => 'dashboard-empty', 'title' => 'Home empty', 'group' => 'Landlord'],
            'shell' => ['view' => 'shell', 'title' => 'Properties', 'group' => 'Landlord'],
            'first-home' => ['view' => 'first-home', 'title' => 'Properties empty', 'group' => 'Landlord'],
            'shell-phone' => ['view' => 'shell-phone', 'title' => 'Properties phone', 'group' => 'Landlord'],
            'finance' => ['view' => 'finance', 'title' => 'Finance', 'group' => 'Landlord'],
            'repairs' => ['view' => 'repairs', 'title' => 'Repairs', 'group' => 'Landlord'],
            'people' => ['view' => 'people', 'title' => 'People', 'group' => 'Landlord'],
            'profile' => ['view' => 'profile', 'title' => 'Settings', 'group' => 'Landlord'],
            'tenant' => ['view' => 'tenant', 'title' => 'Home', 'group' => 'Tenant phone'],
            'tenant-rent' => ['view' => 'tenant-rent', 'title' => 'Rent', 'group' => 'Tenant phone'],
            'tenant-repairs' => ['view' => 'tenant-repairs', 'title' => 'Repairs', 'group' => 'Tenant phone'],
            'tenant-docs' => ['view' => 'tenant-docs', 'title' => 'Docs', 'group' => 'Tenant phone'],
            'tenant-me' => ['view' => 'tenant-me', 'title' => 'Me', 'group' => 'Tenant phone'],
            'tenancies' => ['view' => 'tenancies', 'title' => 'Tenancies (parked)', 'group' => 'Parked'],
            'documents' => ['view' => 'documents', 'title' => 'Documents (parked)', 'group' => 'Parked'],
            'calendar' => ['view' => 'calendar', 'title' => 'Calendar (parked)', 'group' => 'Parked'],
            'components' => ['view' => 'components', 'title' => 'Kit', 'group' => 'Parked'],
        ];

        $screen = (string) $request->query('screen', 'dashboard');
        if (! isset($screens[$screen])) {
            $screen = 'dashboard';
        }

        return view('backend.ui-lab.index', [
            'screen' => $screen,
            'screens' => $screens,
            'title' => $screens[$screen]['title'].' · Resisquare lab',
        ]);
    }
}
