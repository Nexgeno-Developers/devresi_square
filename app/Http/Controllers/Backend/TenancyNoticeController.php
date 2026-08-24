<?php

namespace App\Http\Controllers\Backend;

use App\Enums\CrmNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Tenancy;
use App\Models\TenancyNotice;
use App\Services\Notifications\CrmNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenancyNoticeController extends Controller
{
    public function store(Request $request, Tenancy $tenancy, CrmNotificationService $notifications): RedirectResponse
    {
        abort_unless((int) $tenancy->account_id === (int) current_account_id(), 404);
        $tenantIds = $tenancy->tenantMembers()->pluck('user_id')->all();
        $validated = $request->validate([
            'notice_type' => ['required', Rule::in(['written_information', 'rent_increase', 'tenant_notice', 'possession_notice'])],
            'recipient_user_id' => ['nullable', Rule::in($tenantIds)],
            'served_at' => ['required', 'date'],
            'effective_at' => ['nullable', 'date', 'after_or_equal:served_at'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $notice = $tenancy->notices()->create($validated + [
            'account_id' => $tenancy->account_id,
            'status' => 'served',
            'created_by' => $request->user()->id,
        ]);
        $recipients = $validated['recipient_user_id']
            ? [$validated['recipient_user_id']]
            : $tenantIds;

        $notifications->dispatch(CrmNotificationEvent::TenancyNoticeServed, $notice, [
            'account_id' => $tenancy->account_id,
            'recipients' => $recipients,
            'milestone' => 'served-'.$notice->id,
            'notice_type' => str_replace('_', ' ', $notice->notice_type),
            'property_address' => $tenancy->property?->full_address ?? "Property #{$tenancy->property_id}",
            'action_url' => route('admin.tenancies.show', $tenancy->id),
        ], $request->user());

        return back()->with('success', 'Tenancy notice recorded and queued for delivery.');
    }
}
