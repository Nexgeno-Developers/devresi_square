<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\UserCategory;
use Illuminate\Http\Request;

class UserCategoryController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = UserCategory::all();
        return view('backend.users.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.users.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:155',
            'status' => 'required|boolean',
        ]);

        UserCategory::create($request->all());
        return redirect()->route('user-categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserCategory $userCategory)
    {
        return view('backend.users.categories.edit', compact('userCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserCategory $userCategory)
    {
        $request->validate([
            'name' => 'required|string|max:155',
            'status' => 'required|boolean',
        ]);

        $userCategory->update($request->all());
        return redirect()->route('user-categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserCategory $userCategory)
    {
        $userCategory->delete();
        return redirect()->route('user-categories.index')->with('success', 'Category deleted successfully.');
    }
}
