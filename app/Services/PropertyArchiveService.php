<?php

namespace App\Services;

use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\Tenancy;
use App\Services\Finance\RentFinanceService;
use App\Services\Saas\PortalAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropertyArchiveService
{
    public function __construct(
        private readonly RentFinanceService $rentFinance,
    ) {
    }

    /**
     * Soft-delete a property and close live tenancy / unpaid rent invoice state.
     */
    public function archive(Property $property, ?int $actorId = null): Property
    {
        if ($property->trashed()) {
            return $property;
        }

        $actorId = $actorId ?? Auth::id();

        return DB::transaction(function () use ($property, $actorId) {
            $locked = Property::query()->whereKey($property->id)->lockForUpdate()->firstOrFail();

            $this->archiveActiveTenancies((int) $locked->id, $actorId);
            $this->voidOpenRentInvoices((int) $locked->id);

            // Soft-deleted homes should not keep a "let agreed" badge if restored empty.
            $locked->forceFill(['letting_current_status' => 'available'])->save();

            $locked->deleted_by = $actorId;
            $locked->save();
            $locked->delete();

            return $locked;
        });
    }

    /**
     * Permanently remove a property after archiving live state.
     */
    public function forceRemove(Property $property, ?int $actorId = null): void
    {
        DB::transaction(function () use ($property, $actorId) {
            $locked = Property::withTrashed()->whereKey($property->id)->lockForUpdate()->firstOrFail();

            if (! $locked->trashed()) {
                $this->archiveActiveTenancies((int) $locked->id, $actorId ?? Auth::id());
                $this->voidOpenRentInvoices((int) $locked->id);
            }

            $locked->forceDelete();
        });
    }

    private function archiveActiveTenancies(int $propertyId, ?int $actorId): void
    {
        $tenancies = Tenancy::query()
            ->where('property_id', $propertyId)
            ->where('status', 'Active')
            ->get();

        $updates = [
            'status' => 'Archived',
            'updated_by' => $actorId,
        ];

        if (\Schema::hasColumn('tenancies', 'deleted_by')) {
            $updates['deleted_by'] = $actorId;
        }

        Tenancy::query()
            ->whereIn('id', $tenancies->pluck('id'))
            ->update($updates);

        $portal = app(PortalAccessService::class);
        foreach ($tenancies as $tenancy) {
            $tenancy->status = 'Archived';
            $portal->closePortalForEndedTenancy($tenancy);
        }
    }

    private function voidOpenRentInvoices(int $propertyId): void
    {
        $invoices = RentInvoice::query()
            ->where('property_id', $propertyId)
            ->where('status', RentInvoice::STATUS_ISSUED)
            ->get();

        foreach ($invoices as $invoice) {
            if ($invoice->payments()->exists()) {
                continue;
            }

            try {
                $this->rentFinance->voidInvoice($invoice);
            } catch (ValidationException $e) {
                // Race: payments landed between query and void — leave as-is.
            }
        }
    }
}
