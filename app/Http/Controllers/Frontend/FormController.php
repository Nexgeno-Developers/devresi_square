<?php

namespace App\Http\Controllers\Frontend;

use App\Models\FormSubmission;
use Illuminate\Http\Request;

class FormController
{
    public function show($type)
    {
        // No standalone form pages exist (forms are embedded, e.g. the book-a-demo modal),
        // so an unknown type is a missing page, not a server error.
        abort_unless(is_string($type) && preg_match('/^[a-z0-9_-]+$/i', $type) && view()->exists("forms.$type"), 404);

        return view("forms.$type", ['formType' => $type]);
    }

    /** Public forms that may be submitted. Anything else is a missing page. */
    private const SUBMITTABLE_TYPES = ['book_demo'];

    public function submit(Request $request, $type)
    {
        abort_unless(in_array($type, self::SUBMITTABLE_TYPES, true), 404);

        $validated = $request->validate([
            'first_name' => 'required|string|max:191',
            'last_name'  => 'nullable|string|max:191',
            'email'      => 'required|email|max:191',
            'phone'      => 'nullable|string|max:20',
            'user_role'  => 'nullable|in:landlord,owner,freelancing_agent,contractor',
            'demo_date'  => 'nullable|date',
            'demo_time'  => 'nullable',
            'hear_about' => 'nullable|string|max:255',
            'subscribe'  => 'sometimes',
            'attachment' => 'nullable|file|max:2048',
            'ip'         => 'nullable|string',
            'ip_data'    => 'nullable|string',
            'ref_url'    => 'nullable|string',
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('form_uploads');
        }

        $validated['form_type'] = $type;
        $validated['subscribe'] = $request->has('subscribe') ? true : false;

        $validated['ip'] = $request->ip();
        // Request headers are not stored: they hold cookies and do not fit the 191-character column.
        unset($validated['ip_data']);
        $validated['ref_url'] = mb_substr((string) $request->headers->get('referer'), 0, 191) ?: null;

        FormSubmission::create($validated);

        // Return JSON for AJAX
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Form submitted successfully.']);
        }

        return back()->with('success', 'Form submitted successfully!');
    }

}
