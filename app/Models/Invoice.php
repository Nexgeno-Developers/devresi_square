<?php

namespace App\Models;

use App\Models\WorkOrder;
use App\Traits\TracksUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory, TracksUser;

    protected $fillable = [
        'invoice_number',
        'work_order_id',
        'property_id',
        'user_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status_id',
        'invoiced_date_time',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItems::class);
    }

    public function payments()
    {
        return $this->hasMany(Transaction::class);
    }

    public function status()
    {
        return $this->belongsTo(InvoiceStatuses::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Sum of completed payments applied to this invoice.
     * Credits add to paid, Debits subtract (refunds/adjustments).
     */
    public function paidAmount(): float
    {
        return (float) $this->payments()
            ->where('status', 'completed')
            ->sum(DB::raw("
                CASE 
                    WHEN transaction_type = 'credit' THEN amount
                    WHEN transaction_type = 'debit' THEN -amount
                    ELSE 0
                END
            "));
    }

    /**
     * Outstanding amount left on the invoice.
     */
    public function outstandingAmount(): float
    {
        return max(0, (float)$this->total_amount - $this->paidAmount());
    }

    /**
     * Apply a payment amount to this invoice.
     * Marks invoice as fully paid if amount settles or exceeds outstanding.
     * 
     * NOTE: set the 'PAID' status id in the config or replace `config('invoices.status.paid')` with your actual status id.
     */
    public function applyPayment(float $amount): void
    {
        // after transaction created we recalc paid amount
        $paid = $this->paidAmount();

        // if fully paid now, set status_id if you use status ids
        if ($paid >= (float)$this->total_amount) {
            // recommended: put mapping in config/invoices.php like ['status' => ['paid' => 3]]
            $paidStatusId = config('invoices.status.paid') ?? null;

            if ($paidStatusId) {
                $this->status_id = $paidStatusId;
                $this->invoiced_date_time = $this->invoiced_date_time ?? now();
                $this->save();
            } else {
                // optional fallback: still save updated invoiced_date_time if fully paid
                $this->invoiced_date_time = $this->invoiced_date_time ?? now();
                $this->save();
            }
        }
    }

    /**
     * Efficient AJAX search used by Select2 or similar.
     *
     * Returns a collection of arrays:
     * [
     *   { id, text, outstanding, property_id, total_amount }
     * ]
     *
     * @param string|null $q
     * @param bool $onlyOutstanding
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function ajaxSearchForSelect(?string $q = null, bool $onlyOutstanding = false, int $limit = 50)
    {
        $query = self::select([
                'invoices.id',
                'invoices.invoice_number',
                'invoices.property_id',
                'invoices.total_amount',
                DB::raw("COALESCE(SUM(
                    CASE 
                        WHEN transactions.status = 'completed' AND transactions.transaction_type = 'credit' THEN transactions.amount
                        WHEN transactions.status = 'completed' AND transactions.transaction_type = 'debit' THEN -transactions.amount
                        ELSE 0 
                    END
                ), 0) as paid_amount"),
            ])
            ->leftJoin('transactions', 'transactions.invoice_id', '=', 'invoices.id')
            ->groupBy('invoices.id', 'invoices.invoice_number', 'invoices.property_id', 'invoices.total_amount');

        if (!is_null($q) && $q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('invoices.invoice_number', 'like', "%{$q}%");

                // if $q is numeric, allow id search too
                if (is_numeric($q)) {
                    $w->orWhere('invoices.id', (int)$q);
                }
            });
        }

        if ($onlyOutstanding) {
            // Use HAVING because of aggregate SUM()
            $query->havingRaw('(invoices.total_amount - COALESCE(SUM(CASE WHEN transactions.status = \'completed\' THEN transactions.amount ELSE 0 END), 0)) > 0');
        }

        $results = $query->orderBy('invoices.invoice_number', 'desc')
            ->limit($limit)
            ->get();

        return $results->map(function ($inv) {
            $paid = (float) $inv->paid_amount;
            $total = (float) $inv->total_amount;
            $outstanding = max(0, $total - $paid);

            return [
                'id' => $inv->id,
                'text' => "{$inv->invoice_number} — £" . number_format($total, 2),
                'outstanding' => $outstanding,
                'property_id' => $inv->property_id,
                'total_amount' => $total,
            ];
        })->values();
    }

    /**
     * Return a single invoice formatted for AJAX/Select2 preselect.
     *
     * @return array
     */
    public function toAjaxData(): array
    {
        // compute paid only for this invoice (single query)
        $paid = (float) $this->payments()->where('status', 'completed')->sum('amount');
        $total = (float) $this->total_amount;
        $outstanding = max(0, $total - $paid);

        return [
            'id' => $this->id,
            'text' => "{$this->invoice_number} — £" . number_format($total, 2),
            'outstanding' => $outstanding,
            'property_id' => $this->property_id,
            'total_amount' => $total,
        ];
    }

}