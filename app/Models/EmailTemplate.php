<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;
        
    protected $table = 'email_templates';
    protected $fillable = [
        'receiver','identifier','email_type','subject','default_text','status'
    ];

    // Return active template by identifier
    public static function getByIdentifier(string $identifier)
    {
        return self::where('identifier', $identifier)->where('status', 1)->first();
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
