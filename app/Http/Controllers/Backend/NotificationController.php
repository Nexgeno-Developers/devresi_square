<?php

namespace App\Http\Controllers\Backend;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Services\Saas\PortalAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use DateTimeZone;
use App\Models\Account;

class NotificationController
{
    public function index(Request $request): View
    {
        $accountId = $this->accountId();
        $query = $request->user()->notifications()->where('account_id', $accountId);

        if ($request->filled('category')) $query->where('category', $request->string('category'));
        if ($request->filled('priority')) $query->where('priority', $request->string('priority'));
        if ($request->input('state') === 'unread') $query->whereNull('read_at');
        if ($request->input('state') === 'read') $query->whereNotNull('read_at');
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date('date_from'));
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date('date_to'));

        $notifications = $query->latest()->paginate(25)->withQueryString();
        $categories = $request->user()->notifications()->where('account_id', $accountId)
            ->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        return view('backend.notifications.index', compact('notifications', 'categories'));
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $item = $request->user()->notifications()
            ->where('account_id', $this->accountId())
            ->findOrFail($notification);
        $item->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()
            ->where('account_id', $this->accountId())
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function preferences(Request $request): View
    {
        $accountId = $this->accountId();
        $definitions = collect(config('crm_notifications.events', []));
        $userPreferences = NotificationPreference::where('account_id', $accountId)
            ->where('user_id', $request->user()->id)->get()->keyBy('event_key');
        $accountPreferences = NotificationPreference::where('account_id', $accountId)
            ->whereNull('user_id')->get()->keyBy('event_key');
        $canManageAccount = $this->canManageAccount($request);
        $accountTimezone = Account::find($accountId)?->timezone ?: 'Europe/London';

        return view('backend.notifications.preferences', compact(
            'definitions', 'userPreferences', 'accountPreferences', 'canManageAccount', 'accountTimezone'
        ));
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $accountId = $this->accountId();
        $eventKeys = array_keys(config('crm_notifications.events', []));
        $validated = $request->validate([
            'scope' => ['required', Rule::in(['user', 'account'])],
            'preferences' => ['nullable', 'array'],
            'preferences.*.event_key' => ['required', Rule::in($eventKeys)],
            'preferences.*.email_enabled' => ['nullable', 'boolean'],
            'preferences.*.in_app_enabled' => ['nullable', 'boolean'],
            'timezone' => ['nullable', Rule::in(DateTimeZone::listIdentifiers())],
        ]);

        if ($validated['scope'] === 'account' && ! $this->canManageAccount($request)) abort(403);
        $userId = $validated['scope'] === 'account' ? null : $request->user()->id;
        if ($validated['scope'] === 'account' && ! empty($validated['timezone'])) {
            Account::whereKey($accountId)->update(['timezone' => $validated['timezone']]);
        }

        foreach ($validated['preferences'] ?? [] as $row) {
            $definition = config('crm_notifications.events', [])[$row['event_key']] ?? null;
            $locked = collect($definition['locked_channels'] ?? []);
            NotificationPreference::updateOrCreate(
                ['account_id' => $accountId, 'user_id' => $userId, 'event_key' => $row['event_key']],
                [
                    'email_enabled' => $locked->contains('email') ? true : ($row['email_enabled'] ?? false),
                    'in_app_enabled' => $locked->contains('system') ? true : ($row['in_app_enabled'] ?? false),
                ]
            );
        }

        return back()->with('success', 'Notification preferences updated.');
    }

    public function deliveries(Request $request): View
    {
        abort_unless($this->canManageAccount($request), 403);
        $query = NotificationLog::where('account_id', $this->accountId())->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('channel')) $query->where('channel', $request->string('channel'));
        if ($request->filled('event_key')) $query->where('identifier', 'like', '%'.$request->string('event_key').'%');
        $deliveries = $query->paginate(30)->withQueryString();

        return view('backend.notifications.deliveries', compact('deliveries'));
    }

    public function retry(Request $request, NotificationLog $delivery): RedirectResponse
    {
        abort_unless($this->canManageAccount($request), 403);
        abort_unless((int) $delivery->account_id === $this->accountId(), 404);

        $delivery->update(['status' => 'pending', 'attempt' => 0, 'error' => null]);
        SendNotificationJob::dispatch($delivery);

        return back()->with('success', 'Notification queued for retry.');
    }

    private function accountId(): int
    {
        $accountId = (int) current_account_id();
        abort_if($accountId <= 0, 403, 'Select an account to view notifications.');
        return $accountId;
    }

    private function canManageAccount(Request $request): bool
    {
        return $request->user()->isSuperAdmin()
            || app(PortalAccessService::class)->isAccountOwnerOrAdmin($request->user(), $this->accountId());
    }
}
