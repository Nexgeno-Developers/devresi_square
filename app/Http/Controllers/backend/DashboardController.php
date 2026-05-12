<?php

namespace App\Http\Controllers\Backend;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\WorkOrder;
use App\Models\RepairIssue;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Tenants don't have a dashboard — send them to their home page
        if ($user->hasRole('Tenant')) {
            return redirect()->route('backend.home');
        }

        $this->authorize('view dashboard');
        // $this->middleware(middleware: 'auth'); // Ensure the user is authenticated
        // $this->middleware('can:view dashboard'); // Optional: Ensure the user has permission to view the dashboard

        if (!$user->hasAnyRole(['Super Admin', 'Landlord', 'Staff', 'Property Manager', 'Estate Agent', 'Test'])) {
            abort(403);
        }

        if ($user->hasAnyRole(['Landlord', 'Estate Agent', 'Staff', 'Test'])) {
            // dd('User is a Landlord, Estate Agent, or Staff');
            $usersCount = User::where('created_by', $user->id)->count();
            $propertiesCount = Property::where('created_by', $user->id)->count();
            $invoicesCount = Invoice::where('created_by', $user->id)->count();
            $workOrdersCount = WorkOrder::where('created_by', $user->id)->count();
            $repairIssuesCount = RepairIssue::where('created_by', $user->id)->count();
           
        } else {
            // dd('User is not a Landlord, Property Manager, Estate Agent, or Staff');
            $usersCount = User::count();
            $propertiesCount = Property::count();
            $invoicesCount = Invoice::count();
            $workOrdersCount = WorkOrder::count();
            $repairIssuesCount = RepairIssue::count();
        }

        return view('backend.dashboard', compact(
            // 'users',
            'usersCount',
            'propertiesCount',
            'invoicesCount',
            'workOrdersCount',
            'repairIssuesCount'
        ));
    }

}
