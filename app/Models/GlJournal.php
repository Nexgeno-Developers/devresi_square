<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\GlAuditLog;

class GlJournal extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'attachments' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (GlJournal $journal) {
            GlAuditLog::record('created', $journal);
        });

        static::updated(function (GlJournal $journal) {
            GlAuditLog::record('updated', $journal, $journal->getOriginal());
        });

        static::deleting(function (GlJournal $journal) {
            GlAuditLog::record('deleted', $journal);
        });
    }

    public function scopeActive($query)
    {
        return $query->whereNull('reversal_of_id')->whereDoesntHave('reversal');
    }

    public function scopeIssueFor($query, string $type, int $sourceId)
    {
        $issueType = $type === 'purchase' ? 'purchase_invoice_issue' : 'sale_invoice_issue';
        $legacyType = $type === 'purchase' ? 'purchase_invoice' : 'sale_invoice';
        $memoPrefix = $type === 'purchase' ? 'Post purchase invoice' : 'Issue invoice';

        return $query->active()
            ->where('source_id', $sourceId)
            ->where(function ($q) use ($issueType, $legacyType, $memoPrefix) {
                $q->where('source_type', $issueType)
                    ->orWhere(function ($q2) use ($legacyType, $memoPrefix) {
                        $q2->where('source_type', $legacyType)
                            ->where('memo', 'like', $memoPrefix . '%');
                    });
            })
            ->orderByDesc('id');
    }

    public static function activeIssueFor(string $type, int $sourceId): ?self
    {
        return self::issueFor($type, $sourceId)->first();
    }

    public static function activeFor(string $sourceType, int $sourceId): ?self
    {
        return self::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereNull('reversal_of_id')
            ->whereDoesntHave('reversal')
            ->orderByDesc('id')
            ->first();
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = [];
        $parts[] = "Journal #{$this->id}";

        if ($this->memo) {
            $parts[] = $this->memo;
        }

        if ($this->date) {
            $parts[] = \Carbon\Carbon::parse($this->date)->format('Y-m-d');
        }

        return implode(' — ', $parts);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GlJournalLine::class, 'gl_journal_id');
    }

    public function reversal(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    public function reversedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }
}
