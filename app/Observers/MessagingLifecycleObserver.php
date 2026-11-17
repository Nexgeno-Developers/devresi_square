<?php

namespace App\Observers;

use App\Models\ComplianceJobOffer;
use App\Models\RepairJobOffer;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Services\Messaging\ConversationProvisioner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Opens and archives chats as tenancies and jobs change. A messaging failure
 * must never break the tenancy or job flow, so errors are only logged; the
 * daily messaging:sync command and page loads repair anything missed.
 */
class MessagingLifecycleObserver
{
    public function saved(Model $model): void
    {
        $this->run($model, false);
    }

    public function deleted(Model $model): void
    {
        $this->run($model, true);
    }

    private function run(Model $model, bool $deleted): void
    {
        if (! config('messaging.enabled', true)) {
            return;
        }

        try {
            $provisioner = app(ConversationProvisioner::class);
            if ($deleted && ($model instanceof RepairJobOffer || $model instanceof ComplianceJobOffer)) {
                $model->status = 'cancelled';
            }
            match (true) {
                $model instanceof Tenancy => $this->tenancy($provisioner, $model, $deleted),
                $model instanceof TenantMember => $this->member($provisioner, $model),
                $model instanceof RepairJobOffer => $model->wasChanged(['status', 'job_status']) || $model->wasRecentlyCreated || $deleted
                    ? $provisioner->syncRepairJob($model) : null,
                $model instanceof ComplianceJobOffer => $model->wasChanged(['status', 'job_status']) || $model->wasRecentlyCreated || $deleted
                    ? $provisioner->syncComplianceJob($model) : null,
                default => null,
            };
        } catch (\Throwable $exception) {
            Log::error('Messaging lifecycle sync failed', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function tenancy(ConversationProvisioner $provisioner, Tenancy $tenancy, bool $deleted): void
    {
        if (! $deleted && ! $tenancy->wasRecentlyCreated && ! $tenancy->wasChanged(['status', 'account_id', 'property_id'])) {
            return;
        }
        if ($deleted) {
            $tenancy->status = 'Deleted';
        }
        $provisioner->syncTenancy($tenancy);
        $provisioner->syncJobsAtProperty($tenancy->property_id ? (int) $tenancy->property_id : null);
    }

    private function member(ConversationProvisioner $provisioner, TenantMember $member): void
    {
        $tenancy = $member->tenancy_id ? Tenancy::query()->find($member->tenancy_id) : null;
        if (! $tenancy) {
            return;
        }
        $provisioner->syncTenancy($tenancy);
        $provisioner->syncJobsAtProperty($tenancy->property_id ? (int) $tenancy->property_id : null);
    }
}
