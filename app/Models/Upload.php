<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAccount;
use App\Services\SecureUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

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
        $stored = app(SecureUploadService::class)->store($file);

        $upload = new self();
        $upload->file_original_name = pathinfo($stored['original_name'], PATHINFO_FILENAME);
        $upload->extension = $stored['extension'];
        $upload->file_name = $stored['path'];
        $upload->user_id = Auth::id();
        $upload->file_size = $stored['size'];
        $upload->type = $stored['type'];
        $upload->account_id = function_exists('current_account_id') ? current_account_id() : null;
        $upload->save();

        return $upload;
    }
}
