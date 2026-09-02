<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Portal\TenantPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantPortalController extends Controller
{
    public function home(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();

        if (! $user?->hasRole('Tenant')) {
            return view('backend.tenant.home');
        }

        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);
        $invoices = $portal->invoicesFor($user, $accountId, $tenancies);
        $repairs = $portal->repairsFor($user, $accountId, $tenancies);
        $openRepairs = $repairs->reject(fn ($repair) => in_array($repair->status, ['Closed', 'Invoice Paid'], true));

        return view('backend.tenant.portal.home', [
            'tenancies' => $tenancies,
            'activeTenancy' => $tenancies->firstWhere('status', 'Active') ?? $tenancies->first(),
            'outstanding' => $invoices->sum(fn ($invoice) => (float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0)),
            'openRepairCount' => $openRepairs->count(),
            'recentInvoices' => $invoices->take(4),
            'recentRepairs' => $repairs->take(4),
        ]);
    }

    public function tenancy(Request $request, TenantPortalService $portal): View
    {
        $this->assertTenant($request);
        $user = $request->user();
        $tenancies = $portal->tenanciesFor($user, current_account_id());

        return view('backend.tenant.portal.tenancy', [
            'tenancies' => $tenancies,
        ]);
    }

    public function rent(Request $request, TenantPortalService $portal): View
    {
        $this->assertTenant($request);
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
        $this->assertTenant($request);
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);

        return view('backend.tenant.portal.maintenance', [
            'repairs' => $portal->repairsFor($user, $accountId, $tenancies),
            'canRaise' => $user->can('create property repair'),
        ]);
    }

    public function documents(Request $request, TenantPortalService $portal): View
    {
        $this->assertTenant($request);
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);

        return view('backend.tenant.portal.documents', [
            'documents' => $portal->documentsFor($user, $accountId, $tenancies),
        ]);
    }

    private function assertTenant(Request $request): void
    {
        abort_unless($request->user()?->hasRole('Tenant'), 403, 'This area is only available to tenants.');
    }
}
