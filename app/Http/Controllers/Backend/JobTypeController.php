<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\JobType;
use Illuminate\Support\Facades\Cache;

class JobTypeController
{
    public function index()
    {
        $jobTypes = JobType::with('children')->orderBy('order_level')->get();
        return view('backend.job_types.index', compact('jobTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:job_types',
            'parent_id' => 'nullable|exists:job_types,id',
            'order_level' => 'integer|min:0'
        ]);

        JobType::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'level' => $request->parent_id ? 2 : 1, // Auto-assign level
            'order_level' => $request->order_level ?? 0
        ]);

        return redirect()->back()->with('success', 'Job Type Added Successfully');
    }

    public function update(Request $request, JobType $jobType)
    {
        $request->validate([
            'name' => 'required|unique:job_types,name,' . $jobType->id,
            'parent_id' => 'nullable|exists:job_types,id',
            'order_level' => 'integer|min:0'
        ]);

        $jobType->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'level' => $request->parent_id ? 2 : 1,
            'order_level' => $request->order_level ?? 0
        ]);

        return redirect()->back()->with('success', 'Job Type Updated');
    }

    public function destroy(JobType $id)
    {
         // Check if the branch exists
         if (!$id) {
            return redirect()->route('admin.job_types.index')->with('error', 'Job type not found.');
        }

        // Delete the branch
        $id->delete();

        flash()->success('Job Type Deleted successfully.');

        return redirect()->back();
    }
    
    // public function getAllJobTypes()
    // {
    //     $jobTypes = Cache::remember('job_types_all', 3600, function () {
    //         return JobType::whereNull('parent_id')->select('id', 'name')->orderBy('order_level')->get();
    //     });

    //     return response()->json($jobTypes);
    // }

    
    // public function getSubTypes($parentId)
    // {
    //     $subTypes = Cache::remember("job_types_sub_$parentId", 3600, function () use ($parentId) {
    //         return JobType::where('parent_id', $parentId)->select('id', 'name')->orderBy('order_level')->get();
    //     });

    //     return response()->json($subTypes);
    // }
    public function getSubTypes($parentId)
    {
        $subTypes = JobType::where('parent_id', $parentId)->orderBy('order_level')->get();
        return response()->json($subTypes);
    }
    

    public function getAllJobTypes()
    {
        $jobTypes = JobType::with('children')->get();
        return response()->json($jobTypes);
    }
    
}

