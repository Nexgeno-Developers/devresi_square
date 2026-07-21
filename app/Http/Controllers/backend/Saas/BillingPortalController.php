<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Http\Controllers\Backend\Saas\Concerns\AuthorizesBillingAccess;
use App\Http\Controllers\Controller;
use App\Services\Saas\StripeCheckoutService;
use Illuminate\Http\Request;

class BillingPortalController extends Controller
{
    use AuthorizesBillingAccess;

    public function create(Request $request, StripeCheckoutService $service)
    {
        $account = $this->billingAccount($request);

        try {
            return redirect()->away($service->createBillingPortalSession($account));
        } catch (\Throwable $e) {
            flash('Could not open Stripe billing portal: ' . $e->getMessage())->error();

            return back();
        }
    }
}
