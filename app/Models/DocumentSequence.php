<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentSequence extends Model
{
    protected $fillable = ['document_type','prefix','next_number','branch_id'];

    // convenience helper for generating next number (DB transaction safe)
    public static function generate(string $documentType, ?string $prefix = null, $branchId = null): string
    {
        return DB::transaction(function () use ($documentType, $prefix, $branchId) {
            $row = DB::table('document_sequences')
                ->where('document_type', $documentType)
                ->where(function($q) use ($branchId) {
                    if (is_null($branchId)) {
                        $q->whereNull('branch_id');
                    } else {
                        $q->where('branch_id', $branchId);
                    }
                })
                ->lockForUpdate()
                ->first();

            if (! $row) {
                DB::table('document_sequences')->insert([
                    'document_type' => $documentType,
                    'prefix' => $prefix,
                    'next_number' => 2,
                    'branch_id' => $branchId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $num = 1;
            } else {
                $num = $row->next_number;
                DB::table('document_sequences')->where('id', $row->id)
                    ->update(['next_number' => DB::raw('next_number + 1'), 'updated_at' => now()]);
                if (is_null($prefix)) {
                    $prefix = $row->prefix;
                }
            }

            return ($prefix ? $prefix . '-' : '') . str_pad($num, 6, '0', STR_PAD_LEFT);
        }, 5);
    }
}
