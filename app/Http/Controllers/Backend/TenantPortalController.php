<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\RentInvoice;
use App\Models\RepairIssue;
use App\Models\RepairJobOffer;
use App\Services\Auth\EmailChangeService;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Contractors\RepairJobAftercareService;
use App\Services\Contractors\RepairJobOfferService;
use App\Services\Finance\RentStripeCheckoutService;
use App\Services\Notifications\SecurityNotice;
use App\Services\Portal\TenancyDetailsConfirmationService;
use App\Services\Portal\TenantPortalService;
use App\Services\Repairs\RepairComplaintClassifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

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
        $upcomingEvents = $events->filter(fn ($event) => $event->start_datetime && $event->start_datetime->gte(now()->startOfDay()))->take(4);

        return view('backend.tenant.portal.home', $this->withConfirmation($request, [
            'tenancies' => $tenancies,
            'activeTenancy' => $tenancies->firstWhere('status', 'Active') ?? $tenancies->first(),
            'outstanding' => $invoices->sum(fn ($invoice) => (float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0)),
            'openRepairCount' => $openRepairs->count(),
            'recentInvoices' => $invoices->take(4),
            'recentRepairs' => $repairs->take(4),
            'upcomingEvents' => $upcomingEvents,
        ]));
    }

    public function tenancy(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();
        $tenancies = $portal->tenanciesFor($user, current_account_id());

        return view('backend.tenant.portal.tenancy', $this->withConfirmation($request, [
            'tenancies' => $tenancies,
        ]));
    }

    public function confirmDetails(Request $request, TenancyDetailsConfirmationService $confirmation): RedirectResponse
    {
        $validated = $request->validate([
            'tenancy_id' => 'required|integer',
        ]);

        $confirmation->confirm($request->user(), current_account_id(), (int) $validated['tenancy_id']);

        flash('Thanks — those tenancy details are confirmed.')->success();

        return redirect()->route('tenant.tenancy');
    }

    public function requestCorrection(Request $request, TenancyDetailsConfirmationService $confirmation): RedirectResponse
    {
        $validated = $request->validate([
            'tenancy_id' => 'required|integer',
            'message' => 'required|string|max:2000',
            'fields' => 'required|array|min:1',
            'fields.*' => ['required', 'string', Rule::in(array_keys(TenancyDetailsConfirmationService::FIELDS))],
            'suggested' => 'nullable|array',
            'suggested.*' => 'nullable|string|max:255',
        ]);

        $confirmation->requestCorrection($request->user(), current_account_id(), (int) $validated['tenancy_id'], $validated);

        flash('We have asked your landlord to review those details.')->success();

        return redirect()->route('tenant.tenancy');
    }

    public function rent(Request $request, TenantPortalService $portal, RentStripeCheckoutService $rentCheckout): View
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);
        $invoices = $portal->invoicesFor($user, $accountId, $tenancies);
        $rentCheckout->reconcileOpenCheckouts($invoices);
        $invoices = $portal->invoicesFor($user, $accountId, $tenancies);
        $outstanding = $invoices->sum(fn ($invoice) => (float) ($invoice->balance_amount ?? $invoice->total_amount ?? 0));
        $cardReady = $rentCheckout->isConfigured();
        $cardPaused = $this->cardPaymentsPaused();
        if ($cardPaused) {
            $cardReady = false;
        }
        $fees = [];
        if ($cardReady) {
            foreach ($invoices as $invoice) {
                if ($invoice->isOpen()) {
                    $fees[$invoice->id] = $rentCheckout->feeBreakdown((float) $invoice->balance);
                }
            }
        }

        return view('backend.tenant.portal.rent', $this->withConfirmation($request, [
            'tenancies' => $tenancies,
            'invoices' => $invoices,
            'outstanding' => $outstanding,
            'cardReady' => $cardReady,
            'cardPaused' => $cardPaused,
            'fees' => $fees,
            'rentBank' => app(\App\Services\Finance\RentFinanceService::class)->rentPayBankDetails($accountId ? (int) $accountId : null),
            'checkoutCancelled' => $request->query('checkout') === 'cancelled',
        ]));
    }

    public function showInvoice(Request $request, TenantPortalService $portal, RentStripeCheckoutService $rentCheckout, RentInvoice $rentInvoice): View
    {
        $user = $request->user();
        $accountId = current_account_id();
        $this->assertTenantInvoice($portal, $user, $accountId, $rentInvoice);
        $rentCheckout->reconcileOpenCheckouts([$rentInvoice]);
        $rentInvoice->refresh();

        $rentInvoice->load(['property', 'tenancy', 'tenant', 'payments']);

        $fee = null;
        $cardReady = $rentCheckout->isConfigured();
        $cardPaused = $this->cardPaymentsPaused();
        if ($cardPaused) {
            $cardReady = false;
        }
        if ($cardReady && $rentInvoice->isOpen()) {
            $fee = $rentCheckout->feeBreakdown((float) $rentInvoice->balance);
        }

        return view('backend.tenant.portal.invoice-show', $this->withConfirmation($request, [
            'invoice' => $rentInvoice,
            'cardReady' => $cardReady,
            'cardPaused' => $cardPaused,
            'fee' => $fee,
            'rentBank' => app(\App\Services\Finance\RentFinanceService::class)->rentPayBankDetails($accountId ? (int) $accountId : null),
            'print' => $request->boolean('print'),
        ]));
    }

    public function pay(Request $request, TenantPortalService $portal, RentStripeCheckoutService $rentCheckout, RentInvoice $rentInvoice): Response
    {
        $user = $request->user();
        $accountId = current_account_id();
        $this->assertTenantInvoice($portal, $user, $accountId, $rentInvoice);
        abort_unless((int) $rentInvoice->tenant_user_id === (int) $user->id, 403);
        abort_unless($rentInvoice->isOpen(), 403);

        try {
            $url = $rentCheckout->createCheckoutUrl($user, $rentInvoice);
        } catch (ValidationException $exception) {
            flash(collect($exception->errors())->flatten()->first() ?: 'Payment Not Completed. The payment could not be completed. Check the payment details and try again.')->error();

            return redirect()->route('tenant.rent');
        } catch (RuntimeException $exception) {
            flash($exception->getMessage())->error();

            return redirect()->route('tenant.rent');
        }

        return redirect()->away($url);
    }

    public function paid(Request $request, RentStripeCheckoutService $rentCheckout): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId === '' || ! str_starts_with($sessionId, 'cs_')) {
            flash('We could not confirm that payment. If you were charged, it will show on this page shortly.')->error();

            return redirect()->route('tenant.rent');
        }

        try {
            $payment = $rentCheckout->fulfillCheckoutSessionId($sessionId);
        } catch (\Throwable $exception) {
            report($exception);
            flash('Your card payment is processing. Refresh this page in a moment if the invoice is still open.')->warning();

            return redirect()->route('tenant.rent');
        }

        if ($payment) {
            flash('Rent payment received. Thank you.')->success();
        } else {
            flash('Your card payment is processing. Refresh this page in a moment if the invoice is still open.')->warning();
        }

        return redirect()->route('tenant.rent');
    }

    private function assertTenantInvoice(TenantPortalService $portal, $user, ?int $accountId, RentInvoice $invoice): void
    {
        $tenancies = $portal->tenanciesFor($user, $accountId);
        $visible = $portal->invoicesFor($user, $accountId, $tenancies)
            ->contains(fn (RentInvoice $visible) => (int) $visible->id === (int) $invoice->id);

        abort_unless($visible, 404);
    }

    public function maintenance(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);

        return view('backend.tenant.portal.maintenance', $this->withConfirmation($request, [
            'repairs' => $portal->repairsFor($user, $accountId, $tenancies),
            'tenancies' => $tenancies,
            'canRaise' => true,
            'priorityComplaints' => app(RepairComplaintClassifier::class)->tenantOptions(),
            'categories' => \App\Models\RepairCategory::tenantAreaChoices(),
        ]));
    }

    public function storeRepair(Request $request, TenantPortalService $portal): RedirectResponse
    {
        $classifier = app(RepairComplaintClassifier::class);
        $validated = $request->validate([
            'property_id' => 'required|integer',
            'description' => ['nullable', 'string', 'max:2000', Rule::requiredIf(function () use ($request, $classifier) {
                $code = (string) $request->input('complaint_code', '');

                return $code === '' || $code === 'other' || ! in_array($code, $classifier->codes(), true);
            })],
            'priority' => 'nullable|in:low,medium,high,critical',
            'complaint_code' => ['nullable', 'string', Rule::in(array_merge($classifier->codes(), ['other']))],
            'repair_category_id' => 'nullable|integer|exists:repair_categories,id',
            'access_details' => 'nullable|string|max:2000',
            'tenant_availability' => ['nullable', 'date_format:Y-m-d\TH:i', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || $value === '') {
                    return;
                }

                $at = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $value);
                $earliest = now()->startOfDay();
                $latest = now()->addDays(60)->endOfDay();
                if ($at->lt($earliest) || $at->gt($latest) || ! in_array($at->format('H:i'), ['09:00', '13:00', '17:00'], true)) {
                    $fail('Choose a morning, afternoon, or evening in the next 60 days.');
                }
            }],
            'photo' => 'nullable|image|max:10240',
        ]);

        if (($validated['complaint_code'] ?? null) === 'other') {
            $validated['complaint_code'] = null;
        }
        if (blank($validated['description'] ?? null) && filled($validated['complaint_code'] ?? null)) {
            $validated['description'] = (string) ($classifier->complaints()[$validated['complaint_code']]['title'] ?? 'Repair');
        }

        $payload = $validated;
        $payload['photo'] = $request->file('photo');

        $repair = $portal->raiseRepair($request->user(), current_account_id(), $payload);

        $message = 'Your repair request has been sent.';
        if ($repair->isPriorityComplaint()) {
            $message = 'Your priority repair has been sent. '.$classifier->duePhrase((array) $repair->classification_snapshot);
        }

        flash($message)->success();

        return redirect()->route('tenant.maintenance');
    }

    public function confirmVisit(
        Request $request,
        RepairIssue $repair,
        TenantPortalService $portal,
        RepairJobOfferService $offers,
        RepairJobAftercareService $aftercare,
    ): RedirectResponse {
        $user = $request->user();
        $accountId = current_account_id();
        $visible = $portal->repairsFor($user, $accountId, $portal->tenanciesFor($user, $accountId))
            ->contains(fn (RepairIssue $row) => (int) $row->id === (int) $repair->id);
        abort_unless($visible, 404);

        $offer = $repair->awardedOffer;
        if (! $offer || ! in_array($offer->status, [RepairJobOffer::JOB_ACCEPTED, RepairJobOffer::INSPECTION_ACCEPTED], true)) {
            $offer = $repair->jobOffers()
                ->whereIn('status', [RepairJobOffer::JOB_ACCEPTED, RepairJobOffer::INSPECTION_ACCEPTED])
                ->latest('id')
                ->first();
        }
        abort_unless($offer, 404);

        try {
            if ($request->input('action') === 'reschedule') {
                $data = $request->validate([
                    'date' => ['required', 'date_format:Y-m-d'],
                    'slot' => ['required', Rule::in(['09:00', '13:00', '17:00'])],
                    'note' => ['nullable', 'string', 'max:500'],
                ]);
                $when = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['slot']);
                $aftercare->requestReschedule($offer, $user, 'tenant', [$when->format('Y-m-d\TH:i')], $data['note'] ?? null);
                flash('Thanks. The contractor will pick one of your times.')->success();

                return redirect()->route('tenant.maintenance');
            }
            $offers->tenantConfirm($offer);
        } catch (ContractorMarketplaceException $exception) {
            flash($exception->getMessage())->warning();

            return redirect()->route('tenant.maintenance');
        }

        flash('Thanks. The contractor knows that time works for you.')->success();

        return redirect()->route('tenant.maintenance');
    }

    public function documents(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);

        return view('backend.tenant.portal.documents', $this->withConfirmation($request, [
            'documents' => $portal->documentsFor($user, $accountId, $tenancies),
        ]));
    }

    public function downloadDocument(Request $request, TenantPortalService $portal, Document $document)
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);
        abort_unless($portal->documentVisibleTo($user, $accountId, $document), 404);

        return $document->downloadResponse();
    }

    public function calendar(Request $request, TenantPortalService $portal): View
    {
        $user = $request->user();
        $accountId = current_account_id();
        $tenancies = $portal->tenanciesFor($user, $accountId);
        $events = $portal->eventsFor($user, $accountId, $tenancies);

        $month = $request->query('month');
        try {
            $cursor = $month
                ? \Carbon\CarbonImmutable::createFromFormat('Y-m', (string) $month)->startOfMonth()
                : now()->toImmutable()->startOfMonth();
        } catch (\Throwable) {
            $cursor = now()->toImmutable()->startOfMonth();
        }

        $monthStart = $cursor->startOfMonth();
        $monthEnd = $cursor->endOfMonth();
        $gridStart = $monthStart->startOfWeek(\Carbon\CarbonInterface::MONDAY);
        $gridEnd = $monthEnd->endOfWeek(\Carbon\CarbonInterface::SUNDAY);

        $byDay = [];
        foreach ($events as $event) {
            if (! $event->start_datetime) {
                continue;
            }
            $dayKey = $event->start_datetime->timezone(config('app.timezone'))->format('Y-m-d');
            $byDay[$dayKey][] = $event;
        }

        $days = [];
        for ($day = $gridStart; $day->lte($gridEnd); $day = $day->addDay()) {
            $key = $day->format('Y-m-d');
            $days[] = [
                'date' => $day,
                'in_month' => $day->month === $monthStart->month,
                'is_today' => $day->isToday(),
                'events' => $byDay[$key] ?? [],
            ];
        }

        return view('backend.tenant.portal.calendar', $this->withConfirmation($request, [
            'upcoming' => $events->filter(fn ($event) => $event->start_datetime && $event->start_datetime->gte(now()->startOfDay()))->values(),
            'past' => $events->filter(fn ($event) => $event->start_datetime && $event->start_datetime->lt(now()->startOfDay()))->reverse()->values(),
            'monthCursor' => $monthStart,
            'prevMonth' => $monthStart->subMonth()->format('Y-m'),
            'nextMonth' => $monthStart->addMonth()->format('Y-m'),
            'calendarDays' => $days,
            'hasAnyEvents' => $events->isNotEmpty(),
        ]));
    }

    public function profile(Request $request): View
    {
        $user = $request->user();
        [$profileFirstName, $profileLastName] = $this->profileNameParts($user);

        return view('backend.tenant.portal.profile', $this->withConfirmation($request, [
            'user' => $user,
            'profileFirstName' => $profileFirstName,
            'profileLastName' => $profileLastName,
            'notices' => $this->noticesFor($user, 5),
        ]));
    }

    public function updateProfile(Request $request, EmailChangeService $emailChanges): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'first_name' => 'required|string|max:55',
            'last_name' => 'nullable|string|max:55',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('users', 'pending_email')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
        ]);

        $user->forceFill([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? '',
            'phone' => $validated['phone'] ?? null,
            'name' => trim($validated['first_name'].' '.($validated['last_name'] ?? '')),
        ])->save();

        $accountId = (int) current_account_id();
        forget_user_address_cache($accountId, (int) $user->id);
        $propertyIds = \App\Models\Tenancy::query()
            ->where('account_id', $accountId)
            ->whereHas('tenantMembers', fn ($query) => $query->where('user_id', $user->id))
            ->pluck('property_id');
        foreach ($propertyIds as $propertyId) {
            forget_property_lookup_cache($accountId, (int) $propertyId);
        }

        $emailResult = $emailChanges->request($user->fresh(), $validated['email']);
        if ($emailResult === 'sent') {
            flash('We sent a confirmation link to '.$user->fresh()->pending_email.'. You still sign in with '.$user->email.' until you open it.')->success();
        } elseif ($emailResult === 'failed') {
            flash('We saved '.$user->fresh()->pending_email.' as a pending email, but the confirmation could not be sent.')->warning();
        } else {
            flash('Your contact details were saved.')->success();
        }

        return redirect()->route('tenant.profile');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (! \Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password)) {
            flash('Current password is incorrect.')->error();

            return back();
        }

        if (\Illuminate\Support\Facades\Hash::check($validated['new_password'], $user->password)) {
            flash('New password cannot be the same as your current password.')->error();

            return back();
        }

        $user->forceFill([
            'password' => \Illuminate\Support\Facades\Hash::make($validated['new_password']),
        ])->save();

        $notices = app(SecurityNotice::class);
        $notices->endOtherSessions($user);
        $notices->inApp(
            (int) (current_account_id() ?: $user->accountUsers()->where('status', 'active')->value('account_id')),
            $user,
            'security.password_changed',
            'Your password was changed',
            'Your password was just changed. If this was not you, reset it and tell your landlord.',
            $user,
            route('tenant.profile')
        );
        $sent = $notices->emailTo(
            (string) $user->email,
            'Your password was changed',
            '<p>Your Resisquare password was just changed. If this was not you, reset it and tell your landlord.</p>'
        );
        if ($sent) {
            flash('Password updated.')->success();
        } else {
            flash('Password updated, but we could not email you about the change.')->warning();
        }

        return redirect()->route('tenant.profile');
    }

    public function notificationPreferences(Request $request): View
    {
        $accountId = (int) current_account_id();
        abort_if($accountId <= 0, 403);

        $allowedPrefixes = ['tenancy.', 'repair.', 'appointment.', 'finance.', 'rent.'];
        $definitions = collect(config('crm_notifications.events', []))
            ->filter(function ($definition, $eventKey) use ($allowedPrefixes) {
                foreach ($allowedPrefixes as $prefix) {
                    if (str_starts_with((string) $eventKey, $prefix)) {
                        return true;
                    }
                }

                return false;
            });

        $userPreferences = \App\Models\NotificationPreference::query()
            ->where('account_id', $accountId)
            ->where('user_id', $request->user()->id)
            ->get()
            ->keyBy('event_key');

        return view('backend.tenant.portal.notifications', $this->withConfirmation($request, [
            'definitions' => $definitions,
            'userPreferences' => $userPreferences,
            'notices' => $this->noticesFor($request->user(), 20),
        ]));
    }

    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        $accountId = (int) current_account_id();
        abort_if($accountId <= 0, 403);

        $allowedKeys = collect(config('crm_notifications.events', []))
            ->keys()
            ->filter(fn ($key) => str_starts_with((string) $key, 'tenancy.')
                || str_starts_with((string) $key, 'repair.')
                || str_starts_with((string) $key, 'appointment.')
                || str_starts_with((string) $key, 'finance.')
                || str_starts_with((string) $key, 'rent.'))
            ->values()
            ->all();

        $validated = $request->validate([
            'preferences' => ['nullable', 'array'],
            'preferences.*.event_key' => ['required', \Illuminate\Validation\Rule::in($allowedKeys)],
            'preferences.*.email_enabled' => ['nullable', 'boolean'],
            'preferences.*.in_app_enabled' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['preferences'] ?? [] as $row) {
            $definition = config('crm_notifications.events', [])[$row['event_key']] ?? null;
            $locked = collect($definition['locked_channels'] ?? []);
            \App\Models\NotificationPreference::updateOrCreate(
                [
                    'account_id' => $accountId,
                    'user_id' => $request->user()->id,
                    'event_key' => $row['event_key'],
                ],
                [
                    'email_enabled' => $locked->contains('email') ? true : (bool) ($row['email_enabled'] ?? false),
                    'in_app_enabled' => $locked->contains('system') ? true : (bool) ($row['in_app_enabled'] ?? false),
                ]
            );
        }

        flash('Notification preferences saved.')->success();

        return redirect()->route('tenant.notifications');
    }

    private function cardPaymentsPaused(): bool
    {
        $account = function_exists('current_account') ? current_account() : null;

        return $account && in_array((string) $account->status, ['suspended', 'cancelled'], true);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\NotificationLog>
     */
    private function noticesFor(\App\Models\User $user, int $limit)
    {
        $accountId = (int) current_account_id();
        $tenancies = app(TenantPortalService::class)->tenanciesFor($user, $accountId);
        $tenancyIds = $tenancies->pluck('id')->map(fn ($id) => (int) $id)->all();
        $propertyIds = $tenancies->pluck('property_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $repairIds = $propertyIds === []
            ? []
            : \App\Models\RepairIssue::query()->whereIn('property_id', $propertyIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $invoiceIds = $tenancyIds === []
            ? []
            : RentInvoice::query()->whereIn('tenancy_id', $tenancyIds)->pluck('id')->map(fn ($id) => (int) $id)->all();

        return \App\Models\NotificationLog::query()
            ->where('account_id', $accountId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', $user->getMorphClass())
            ->where('channel', 'system')
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->filter(fn ($notice) => $this->noticeBelongsToTenant($notice, $tenancyIds, $propertyIds, $repairIds, $invoiceIds))
            ->unique(fn ($notice) => mb_strtolower(trim(rs_notice_text($notice->subject).'|'.rs_notice_text($notice->message))))
            ->take($limit)
            ->values();
    }

    /**
     * @param  list<int>  $tenancyIds
     * @param  list<int>  $propertyIds
     * @param  list<int>  $repairIds
     * @param  list<int>  $invoiceIds
     */
    private function noticeBelongsToTenant($notice, array $tenancyIds, array $propertyIds, array $repairIds, array $invoiceIds): bool
    {
        $type = class_basename((string) $notice->subject_type);
        $id = (int) $notice->subject_id;
        if ($id <= 0 || $type === '') {
            return true;
        }

        return match ($type) {
            'Tenancy' => in_array($id, $tenancyIds, true),
            'Property' => in_array($id, $propertyIds, true),
            'RepairIssue' => in_array($id, $repairIds, true),
            'RentInvoice' => in_array($id, $invoiceIds, true),
            default => true,
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function profileNameParts(\App\Models\User $user): array
    {
        $first = trim((string) $user->first_name);
        $last = trim((string) $user->last_name);

        if ($first === '') {
            $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
            $first = (string) ($parts[0] ?? '');
            if ($last === '') {
                $last = (string) ($parts[1] ?? '');
            }
        }

        return [$first, $last];
    }

    private function withConfirmation(Request $request, array $data): array
    {
        $data['tenancyConfirmation'] = app(TenancyDetailsConfirmationService::class)
            ->payloadFor($request->user(), current_account_id());

        return $data;
    }
}
