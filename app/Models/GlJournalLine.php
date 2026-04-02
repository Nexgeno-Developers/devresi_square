<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Company;
use App\Models\User;
use App\Models\GlAuditLog;

class GlJournalLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'gl_journal_id','gl_account_id','company_id','user_id','debit','credit'
    ];

    protected static function booted(): void
    {
        static::created(function (GlJournalLine $line) {
            GlAuditLog::record('created', $line);
        });

        static::updated(function (GlJournalLine $line) {
            GlAuditLog::record('updated', $line, $line->getOriginal());
        });

        static::deleting(function (GlJournalLine $line) {
            GlAuditLog::record('deleted', $line);
        });
    }

    public function journal()
    {
        return $this->belongsTo(GlJournal::class, 'gl_journal_id');
    }

    public function account()
    {
        return $this->belongsTo(GlAccount::class, 'gl_account_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
