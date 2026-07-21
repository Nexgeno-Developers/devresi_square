<?php

namespace App\Models;

use App\Models\DocumentType;
use App\Models\Upload;
use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes, BelongsToSaasAccount;

    protected $fillable = [
        'account_id',
        'documentable_id',
        'documentable_type',
        'upload_ids',
        'document_type_id',
        'visibility',
        'created_by',
    ];

    /**
     * The actual Upload record holding file info.
     */
    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function documentable()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
