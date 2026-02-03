<?php

namespace App\Http\Controllers\Frontend;

use App\Models\FormSubmission;
use Illuminate\Http\Request;

class FormController
{
    public function show($type)
    {
        return view("forms.$type", ['formType' => $type]);
    }

    public function submit(Request $request, $type)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'demo_date' => 'nullable|date',
            'demo_time' => 'nullable',
            'hear_about' => 'nullable|string|max:255',
            'subscribe' => 'sometimes',
            'attachment' => 'nullable|file|max:2048',
            'ip' => 'nullable|string',
            'ip_data' => 'nullable|string',
            'ref_url' => 'nullable|string',
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('form_uploads');
        }

        $validated['form_type'] = $type;
        $validated['subscribe'] = $request->has('subscribe') ? true : false;

        $validated['ip'] = $request->ip();
        $validated['ip_date'] = json_encode(request()->header()); // or geo-ip service
        $validated['ref_url'] = $request->headers->get('referer');

        FormSubmission::create($validated);

        // Return JSON for AJAX
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Form submitted successfully.']);
        }

        return back()->with('success', 'Form submitted successfully!');
    }

}
