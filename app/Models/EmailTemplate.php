<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EmailTemplate extends Model
{
    use HasFactory;
        
    protected $table = 'email_templates';
    protected $fillable = [
        'account_id','receiver','identifier','email_type','subject','default_text','status'
    ];

    // Return active template by identifier
    public static function getByIdentifier(string $identifier)
    {
        $query = self::where('identifier', $identifier)->where('status', 1);
        $accountId = function_exists('current_account_id') ? current_account_id() : null;

        return $accountId ? $query->forAccount($accountId)->first() : $query->whereNull('account_id')->first();
    }

    public function scopeForAccount(Builder $query, int|string|null $accountId): Builder
    {
        return $query
            ->where(function (Builder $templateQuery) use ($accountId) {
                $templateQuery->where('account_id', $accountId)->orWhereNull('account_id');
            })
            ->orderByRaw('CASE WHEN account_id IS NULL THEN 1 ELSE 0 END');
    }

    public function replace(array $placeholders = [], array $rawKeys = []): string
    {
        $templateHtml = $this->default_text ?? ''; // use the DB column

        if (empty($templateHtml)) {
            \Log::error("Email template '{$this->identifier}' has no content.");
            return '';
        }

        return render_template($templateHtml, $placeholders, $rawKeys);
    }

}
