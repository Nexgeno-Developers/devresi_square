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
            'user_id'    => 'required|exists:users,id',
            'account_name'  => 'required|string|max:255',
            'account_no'    => 'required|string|max:255',
            'sort_code'     => 'required|string|max:255',
            'bank_name'     => 'required|string|max:255',
            'swift_code'    => 'nullable|string|max:255',
            'is_active'     => 'nullable|boolean',
            'is_primary'    => 'nullable|boolean',
            'bank_detail_id'=> 'nullable|exists:bank_details,id',
        ]);

        $data['is_active'] = $request->has('is_active');
        $data['is_primary'] = $request->has('is_primary');

        if (!empty($data['is_primary'])) {
            // Set all others as non-primary for the user
            BankDetails::where('user_id', $data['user_id'])->update(['is_primary' => false]);
        }

        if ($data['bank_detail_id'] ?? false) {
            // Update existing bank detail
            $bank = BankDetails::where('user_id', $data['user_id'])
                    ->findOrFail($data['bank_detail_id']);
            $bank->update($data);
        } else {
            // Create new bank detail
            BankDetails::create($data);
        }

        return response()->json(['success' => true, 'message' => 'Bank detail saved successfully.']);
    }

    /*public function showBankDetail($id)
    {
        $bankDetail = BankDetails::findOrFail($id);

        // Optional: Add additional authorization checks here if needed

        return response()->json([
            'bank_detail' => [
                'account_name'   => $bankDetail->account_name,
                'account_no'     => $bankDetail->account_no,
                'sort_code'      => $bankDetail->sort_code,
                'bank_name'      => $bankDetail->bank_name,
                'swift_code'     => $bankDetail->swift_code,
                'is_active'      => $bankDetail->is_active,
                'is_primary'     => $bankDetail->is_primary,
            ],
        ]);
    }*/

    public function show($id)
    {
        $bankDetail = BankDetails::findOrFail($id);

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
                <dd class="col-sm-8">' . e($bankDetail->swift_code ?? '<span class="text-muted">N/A</span>') . '</dd>

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
        $bank = BankDetails::findOrFail($id);
        $bank->delete();
        $response = [
            'status' => true,
            // 'notification' => 'Bank detail deleted successfully!',
            'message' => 'Bank detail deleted successfully!',
        ];

        return response()->json($response);
        // return response()->json(['success' => true, 'message' => 'Bank detail deleted successfully.']);
    }
}