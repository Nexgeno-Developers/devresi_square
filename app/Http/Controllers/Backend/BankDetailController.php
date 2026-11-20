<?php

namespace App\Http\Controllers\Backend;

use App\Models\BankDetails;
use App\Models\User;
use Illuminate\Http\Request;

class BankDetailController
{
    /**
     * Store or update a bank detail.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'account_name' => 'required|string|max:255',
            'account_no' => 'required|string|max:255',
            'sort_code' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_primary' => 'nullable|boolean',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
        ]);

        $accountId = current_account_id();
        abort_unless($accountId || auth()->user()?->isSuperAdmin(), 403);

        // Contact must belong to the active workspace (Super Admin may target any membership).
        if (! bypasses_account_scope()) {
            User::query()->forAccount($accountId)->findOrFail($data['user_id']);
        }
        // Only the person themselves, or someone who manages the workspace, may change bank details.
        $this->ensureMayChangeBankDetailsFor((int) $data['user_id']);

        $data['is_active'] = $request->has('is_active');
        $data['is_primary'] = $request->has('is_primary');
        $data['account_id'] = $accountId;

        $scoped = fn () => BankDetails::query()
            ->when(
                ! bypasses_account_scope(),
                fn ($query) => $query->forAccount($accountId)
            );

        if (! empty($data['is_primary'])) {
            $scoped()->where('user_id', $data['user_id'])->update(['is_primary' => false]);
        }

        if ($data['bank_detail_id'] ?? false) {
            $bank = $scoped()
                ->where('user_id', $data['user_id'])
                ->findOrFail($data['bank_detail_id']);
            unset($data['bank_detail_id']);
            $bank->update($data);
        } else {
            unset($data['bank_detail_id']);
            BankDetails::create($data);
        }

        return response()->json(['success' => true, 'message' => 'Bank detail saved successfully.']);
    }

    public function show($id)
    {
        $bankDetail = $this->bankDetailForCurrentAccount((int) $id);

        $content = '
        <div class="container py-3">
            <dl class="row">
                <dt class="col-sm-4">Bank Name:</dt>
                <dd class="col-sm-8">' . e($bankDetail->bank_name) . '</dd>

                <dt class="col-sm-4">Account Name:</dt>
                <dd class="col-sm-8">' . e($bankDetail->account_name) . '</dd>

                <dt class="col-sm-4">Account No:</dt>
                <dd class="col-sm-8">' . e($bankDetail->account_no) . '</dd>

                <dt class="col-sm-4">Sort Code:</dt>
                <dd class="col-sm-8">' . e($bankDetail->sort_code) . '</dd>

                <dt class="col-sm-4">SWIFT Code:</dt>
                <dd class="col-sm-8">' . e($bankDetail->swift_code ?? 'N/A') . '</dd>

                <dt class="col-sm-4">Is Active:</dt>
                <dd class="col-sm-8">' . ($bankDetail->is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>') . '</dd>

                <dt class="col-sm-4">Is Primary:</dt>
                <dd class="col-sm-8">' . ($bankDetail->is_primary ? '<span class="badge bg-primary">Yes</span>' : '<span class="badge bg-secondary">No</span>') . '</dd>
            </dl>
        </div>
        ';

        return response()->json([
            'content' => $content,
        ]);
    }

    /**
     * Delete a bank detail.
     */
    public function destroy($id)
    {
        $bank = $this->bankDetailForCurrentAccount((int) $id);
        $this->ensureMayChangeBankDetailsFor((int) $bank->user_id);
        $bank->delete();

        return response()->json([
            'status' => true,
            'message' => 'Bank detail deleted successfully!',
        ]);
    }

    private function ensureMayChangeBankDetailsFor(int $ownerUserId): void
    {
        $actor = auth()->user();
        if (! $actor || (int) $actor->id === $ownerUserId) {
            abort_unless($actor, 403);

            return;
        }

        abort_unless(\App\Support\WorkspaceAccess::canManageCurrentWorkspace($actor), 403);
    }

    private function bankDetailForCurrentAccount(int $id): BankDetails
    {
        $query = BankDetails::query();

        if (! bypasses_account_scope()) {
            $query->forAccount(current_account_id());
        }

        $bank = $query->findOrFail($id);
        ensureModelBelongsToCurrentAccount($bank);

        return $bank;
    }
}
