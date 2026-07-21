<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Upload extends Model
{
    use SoftDeletes, BelongsToAccount;

    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $fillable = [
        'account_id', 'file_original_name', 'file_name', 'user_id', 'extension', 'type', 'file_size', 'visibility',
    ];

    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public static function storeFile($file)
    {
        // Define file types based on extension
        $types = [
            'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image', 'webp' => 'image', 'svg' => 'image',
            'pdf' => 'document', 'doc' => 'document', 'docx' => 'document', 'txt' => 'document',
            'xls' => 'document', 'xlsx' => 'document', 'csv' => 'document'
        ];
    
        $upload = new self();
        $upload->file_original_name = $file->getClientOriginalName();
        $upload->extension = strtolower($file->getClientOriginalExtension());
        $upload->file_name = $file->store('uploads/all', 'public'); // Store file
        $upload->user_id = auth()->id();
        $upload->file_size = $file->getSize();
        $upload->account_id = function_exists('current_account_id') ? current_account_id() : null;
    
        // Set the file type based on extension
        $upload->type = $types[$upload->extension] ?? 'document'; // Default to 'document' if not listed
    
        $upload->save();
    
        return $upload;
    }
    

}
