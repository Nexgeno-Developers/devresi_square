<?php

namespace App\Http\Controllers\Backend;

use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SmsTemplateController extends Controller
{
    public function index()
    {
        $templates = SmsTemplate::all();
        return view('backend.otp.sms_templates', compact('templates'));
    }

    public function edit($id)
    {
        $template = SmsTemplate::findOrFail($id);
        return view('backend.otp.sms_template_edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = SmsTemplate::findOrFail($id);

        $request->validate([
            'sms_body'    => 'required|string',
            'template_id' => 'nullable|string|max:100',
            'status'      => 'required|in:0,1',
        ]);

        $template->update([
            'sms_body'    => $request->sms_body,
            'template_id' => $request->template_id,
            'status'      => $request->status,
        ]);

        flash('SMS template updated.')->success();
        return redirect()->route('sms-templates.index');
    }
}
