<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RepairIssue;
use App\Models\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCommandSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user(), 403);

        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $accountId = current_account_id();
        $like = '%'.addcslashes($term, '%_\\').'%';
        $results = [];

        Property::query()
            ->when($accountId, fn ($query) => $query->forAccount($accountId))
            ->where(function ($query) use ($like) {
                $query->where('line_1', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('postcode', 'like', $like)
                    ->orWhere('prop_name', 'like', $like);
            })
            ->orderBy('line_1')
            ->limit(5)
            ->get()
            ->each(function (Property $property) use (&$results) {
                $results[] = [
                    'label' => $property->line_1 ?: ($property->prop_name ?: 'Property'),
                    'meta' => 'Property',
                    'url' => route('admin.properties.index', ['property_id' => $property->id]),
                ];
            });

        Tenancy::query()
            ->with(['property', 'tenantMembers.user'])
            ->when($accountId, fn ($query) => $query->forAccount($accountId))
            ->where(function ($query) use ($like) {
                $query->whereHas('property', function ($property) use ($like) {
                    $property->where('line_1', 'like', $like)->orWhere('postcode', 'like', $like);
                })->orWhereHas('tenantMembers.user', function ($user) use ($like) {
                    $user->where('name', 'like', $like)->orWhere('email', 'like', $like);
                });
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->each(function (Tenancy $tenancy) use (&$results) {
                $tenant = $tenancy->tenantMembers->map(fn ($member) => $member->user?->name)->filter()->first();
                $results[] = [
                    'label' => $tenant ?: ($tenancy->property?->line_1 ?: 'Tenancy'),
                    'meta' => 'Tenancy',
                    'url' => route('admin.tenancies.show', $tenancy->id),
                ];
            });

        RepairIssue::query()
            ->with('property')
            ->when($accountId, fn ($query) => $query->forAccount($accountId))
            ->where(function ($query) use ($like) {
                $query->where('description', 'like', $like)
                    ->orWhere('reference_number', 'like', $like)
                    ->orWhereHas('property', fn ($property) => $property->where('line_1', 'like', $like));
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->each(function (RepairIssue $repair) use (&$results) {
                $results[] = [
                    'label' => \Illuminate\Support\Str::limit($repair->description ?: 'Repair', 80),
                    'meta' => 'Repair',
                    'url' => route('admin.property_repairs.show', $repair->id),
                ];
            });

        return response()->json(['results' => array_slice($results, 0, 12)]);
    }
}
