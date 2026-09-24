<?php

namespace App\Services\Documents;

use App\Enums\CrmNotificationEvent;
use App\Models\Document;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Services\Notifications\CrmNotificationService;
use Illuminate\Support\Collection;

class DocumentShareNotifier
{
    public function __construct(private readonly CrmNotificationService $notifications)
    {
    }

    public function shared(Document $document): void
    {
        if (! $document->isSharedWithTenant()) {
            return;
        }

        $document->loadMissing('documentType');
        $recipients = $this->recipients($document);
        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->dispatch(CrmNotificationEvent::DocumentShared, $document, [
            'account_id' => (int) $document->account_id,
            'recipients' => $recipients,
            'document_title' => $document->displayName(),
            'milestone' => 'shared-'.$document->id.'-'.$document->updated_at?->timestamp,
            'action_url' => route('admin.documents.index'),
            'portal_action_url' => route('tenant.documents'),
            'portal_action_tenants_only' => true,
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    public function recipients(Document $document): Collection
    {
        $type = (string) $document->documentable_type;
        $id = (int) $document->documentable_id;
        $users = collect();

        if ($this->matches($type, Property::class, 'Property')) {
            $users = TenantMember::query()
                ->where('account_id', $document->account_id)
                ->whereHas('tenancy', fn ($query) => $query->where('property_id', $id)->where('status', 'Active'))
                ->with('user')
                ->get()
                ->pluck('user');
        } elseif ($this->matches($type, Tenancy::class, 'Tenancy')) {
            $users = TenantMember::query()
                ->where('tenancy_id', $id)
                ->where('account_id', $document->account_id)
                ->with('user')
                ->get()
                ->pluck('user');
        } elseif ($this->matches($type, User::class, 'User')) {
            $users = User::query()->whereKey($id)->get();
        }

        return $users->filter(fn ($user) => $user instanceof User && filled($user->email))->unique('id')->values();
    }

    private function matches(string $stored, string $class, string $short): bool
    {
        $morph = class_exists($class) ? (new $class)->getMorphClass() : $class;

        return in_array($stored, [$class, $morph, $short], true);
    }
}
