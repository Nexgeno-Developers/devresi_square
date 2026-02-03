<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\TransactionCategory;
use Illuminate\Http\Request;

class TransactionCategoryController extends Controller
{
    /**
     * Display a listing of transaction categories.
     */
    public function index()
    {
        $categories = TransactionCategory::latest()->paginate(20);

        return view('backend.transaction_categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new transaction category.
     */
    public function create()
    {
        return view('backend.transaction_categories.create');
    }

    /**
     * Store a newly created transaction category in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255|unique:transaction_categories,name',
            'code'      => 'nullable|string|max:50|unique:transaction_categories,code',
            'is_income' => 'required|boolean',
            'is_active' => 'required|boolean',
            'is_system' => 'nullable|boolean',
        ]);

        TransactionCategory::create($validated);

        return redirect()
            ->route('backend.transaction_categories.index')
            ->with('success', 'Transaction Category created successfully.');
    }

    /**
     * Display the specified category.
     */
    public function show(TransactionCategory $transaction_category)
    {
        return view('backend.transaction_categories.show', compact('transaction_category'));
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(TransactionCategory $transaction_category)
    {
        return view('backend.transaction_categories.edit', compact('transaction_category'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, TransactionCategory $transaction_category)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255|unique:transaction_categories,name,' . $transaction_category->id,
            'code'      => 'nullable|string|max:50|unique:transaction_categories,code,' . $transaction_category->id,
            'is_income' => 'required|boolean',
            'is_active' => 'required|boolean',
            'is_system' => 'nullable|boolean',
        ]);

        $transaction_category->update($validated);

        return redirect()
            ->route('backend.transaction_categories.index')
            ->with('success', 'Transaction Category updated successfully.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(TransactionCategory $transaction_category)
    {
        if ($transaction_category->is_system) {
            return redirect()
                ->route('backend.transaction_categories.index')
                ->with('error', 'System categories cannot be deleted.');
        }

        $transaction_category->delete();

        return redirect()
            ->route('backend.transaction_categories.index')
            ->with('success', 'Transaction Category deleted successfully.');
    }
}
