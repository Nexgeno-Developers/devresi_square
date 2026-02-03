<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Mail\EmailManager;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailTemplateController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:manage_email_templates'])->only('index', 'edit', 'update');
    }

    public function index(Request $request, $emailReceiver)
    {
        $email_template_sort_search = (isset($request->email_template_sort_search) && $request->email_template_sort_search) ? $request->email_template_sort_search : null;
        $emailTemplates = EmailTemplate::where('receiver', $emailReceiver);

        if ($email_template_sort_search != null){
            $notificationTypes = $emailTemplates->where('email_type', 'like', '%' . $email_template_sort_search . '%');
        }
        $emailTemplates = $emailTemplates->paginate(10);
        return view('backend.setup_configurations.email_templates.index', compact('emailTemplates', 'email_template_sort_search', 'emailReceiver'));
    }

    public function create()
    {
        return view('backend.setup_configurations.email_templates.create');
    }

    public function store(Request $request)
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->subject = $request->subject;
        $emailTemplate->default_text = $request->default_text;
        $emailTemplate->receiver = $request->receiver;
        $emailTemplate->save();

        flash('Email Template has been created successfully')->success();
        return redirect()->route('backend.email_templates.index');
    }

    public function show($id)
    {
        $emailTemplate = EmailTemplate::findOrFail($id);
        return view('backend.setup_configurations.email_templates.show', compact('emailTemplate'));
    }

    public function edit($id)
    {
        $emailTemplate  = EmailTemplate::findOrFail($id);
        return view('backend.setup_configurations.email_templates.edit', compact('emailTemplate'));
    }

    
    public function update(Request $request, $id)
    {
        $emailTemplate = EmailTemplate::findOrFail($id);
        $emailTemplate->subject = $request->subject;
        $emailTemplate->default_text = $request->default_text;
        $emailTemplate->save();

        flash('Email Template has been updated successfully')->success();
        return back();
    }

    public function updateStatus(Request $request) {
        $emailTemplate = EmailTemplate::findOrFail($request->id);
        $emailTemplate->status = $request->status;
        $emailTemplate->save();
        return 1;
    }

    public function destroy($id)
    {
        $emailTemplate = EmailTemplate::findOrFail($id);
        $emailTemplate->delete();

        flash('Email Template has been deleted successfully')->success();
        return back();
    }

    public function testEmail(Request $request){
        $array['view'] = 'emails.newsletter';
        $array['subject'] = "SMTP Test";
        $array['from'] = env('MAIL_FROM_ADDRESS');
        $array['content'] = "This is a test email.";

        try {
            Mail::to($request->email)->send(new EmailManager($array));
        } catch (\Exception $e) {
            // dd($e);
            Log::error('SMTP Test Email Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Email could not be sent. Please check logs.');
        }

        flash('An email has been sent.')->success();
        return back();
    }
}
