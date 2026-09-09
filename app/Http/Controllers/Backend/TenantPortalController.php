<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\Portal\TenantPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantPortalController extends Controller
{
    public function home(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);
        $invoices = $portal->invoicesFor($user, $accountId, $tenancies);
        $repairs = $portal->repairsFor($user, $accountId, $tenancies);
        $openRepairs = $repairs->reject(fn ($repair) => in_array($repair->status, ['Closed', 'Invoice Paid'], true));
        $events = $portal->eventsFor($user, $accountId, $tenancies);
        $upcomingEvents = $events->filter(fn ($event) => $event->start_datetime && $event->start_datetime->gte(now()))->take(4);

        return view('backend.tenant.portal.home', [
            'tenancies' => $tenancies,
            'activeTenancy' => $tenancies->firstWhere('status', 'Active') ?? $tenancies->first(),
            'outstanding' => $invoices->sum(fn ($invoice) => (float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0)),
            'openRepairCount' => $openRepairs->count(),
            'recentInvoices' => $invoices->take(4),
            'recentRepairs' => $repairs->take(4),
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    public function tenancy(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();
        $tenancies = $portal->tenanciesFor($user, current_account_id());

        return view('backend.tenant.portal.tenancy', [
            'tenancies' => $tenancies,
        ]);
    }

    public function rent(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);
        $invoices = $portal->invoicesFor($user, $accountId, $tenancies);

        return view('backend.tenant.portal.rent', [
            'tenancies' => $tenancies,
            'invoices' => $invoices,
            'outstanding' => $invoices->sum(fn ($invoice) => (float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0)),
        ]);
    }

    public function maintenance(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);

        return view('backend.tenant.portal.maintenance', [
            'repairs' => $portal->repairsFor($user, $accountId, $tenancies),
            'tenancies' => $tenancies,
            'canRaise' => true,
        ]);
    }

    public function storeRepair(Request $request, TenantPortalService $portal): RedirectResponse
    {
        $validated = $request->validate([
            'property_id' => 'nullable|integer',
            'description' => 'required|string|max:2000',
            'priority' => 'nullable|in:low,medium,high,critical',
        ]);

        $portal->raiseRepair($request->user(), current_account_id(), $validated);

        flash('Your repair request has been sent.')->success();

        return redirect()->route('tenant.maintenance');
    }

    public function documents(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);

        return view('backend.tenant.portal.documents', [
            'documents' => $portal->documentsFor($user, $accountId, $tenancies),
        ]);
    }

    public function downloadDocument(Request $request, TenantPortalService $portal, Document $document)
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);
        $visible = $portal->documentsFor($user, $accountId, $tenancies)
            ->contains(fn (Document $visible) => (int) $visible->id === (int) $document->id);

        abort_unless($visible, 404);

        return $document->downloadResponse();
    }

    public function calendar(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);
        $events = $portal->eventsFor($user, $accountId, $tenancies);

        return view('backend.tenant.portal.calendar', [
            'upcoming' => $events->filter(fn ($event) => $event->start_datetime && $event->start_datetime->gte(now()))->values(),
            'past' => $events->filter(fn ($event) => $event->start_datetime && $event->start_datetime->lt(now()))->reverse()->values(),
        ]);
    }
}
