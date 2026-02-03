<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Upload extends Model
{
    use SoftDeletes;

    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $fillable = [
        'file_original_name', 'file_name', 'user_id', 'extension', 'type', 'file_size',
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
    
        // Set the file type based on extension
        $upload->type = $types[$upload->extension] ?? 'document'; // Default to 'document' if not listed
    
        $upload->save();
    
        return $upload;
    }
    

}
