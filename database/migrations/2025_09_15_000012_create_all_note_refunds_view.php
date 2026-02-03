<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use a view that returns a unified refund feed.
        $sql = <<<SQL
CREATE OR REPLACE VIEW all_note_refunds AS
SELECT
    CONCAT('credit_', cr.id) AS unified_id,
    'credit' AS note_kind,
    cr.id AS refund_id,
    cr.credit_note_id AS note_id,
    cn.note_number AS note_number,
    cr.amount,
    cr.refund_date,
    cr.status,
    cr.transaction_number,
    cr.reference,
    cr.notes,
    cr.processed_by,
    cr.created_at
FROM credit_note_refunds cr
LEFT JOIN credit_notes cn ON cr.credit_note_id = cn.id

UNION ALL

SELECT
    CONCAT('debit_', dr.id) AS unified_id,
    'debit' AS note_kind,
    dr.id AS refund_id,
    dr.debit_note_id AS note_id,
    dn.note_number AS note_number,
    dr.amount,
    dr.refund_date,
    dr.status,
    dr.transaction_number,
    dr.reference,
    dr.notes,
    dr.processed_by,
    dr.created_at
FROM debit_note_refunds dr
LEFT JOIN debit_notes dn ON dr.debit_note_id = dn.id;
SQL;
        DB::statement($sql);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS all_note_refunds');
    }
};
