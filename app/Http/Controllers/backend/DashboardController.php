<?php

namespace App\Http\Controllers\Backend;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController
{
    public function dashboard()
    {
       
        // Fetch all users with their roles
        $users = User::with('role')->get(); // Assuming the role relationship is defined in the User model
        
        // Pass users to the view
        return view('backend.dashboard', compact('users'));
    }

}
