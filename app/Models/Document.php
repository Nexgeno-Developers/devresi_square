<?php

namespace App\Models;

use App\Models\DocumentType;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'documentable_id',
        'documentable_type',
        'upload_ids',
        'document_type_id',
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
}
