<?php

namespace App\Models;

use App\Models\DocumentType;
use App\Models\Upload;
use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Document extends Model
{
    use SoftDeletes, BelongsToSaasAccount;

    public const TENANT_VISIBILITIES = ['shared', 'portal'];

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

    public function isSharedWithTenant(): bool
    {
        return in_array((string) $this->visibility, self::TENANT_VISIBILITIES, true);
    }

    public function firstUpload(): ?Upload
    {
        $id = (int) trim((string) explode(',', (string) $this->upload_ids)[0]);

        if ($id <= 0) {
            return null;
        }

        return Upload::query()
            ->when($this->account_id, fn ($query) => $query->where('account_id', $this->account_id))
            ->find($id);
    }

    public function downloadResponse(): BinaryFileResponse
    {
        $upload = $this->firstUpload();
        abort_unless($upload, 404, 'This document has no file.');

        $path = $this->absolutePathFor($upload);
        abort_unless($path, 404, 'The file is missing.');

        $name = $upload->file_original_name ?: basename($path);

        return response()->download($path, $name);
    }

    private function absolutePathFor(Upload $upload): ?string
    {
        $name = ltrim((string) $upload->file_name, '/');

        if ($name === '') {
            return null;
        }

        $candidates = [
            Storage::disk('public')->path($name),
            storage_path('app/public/'.$name),
            public_path($name),
            base_path('public/'.$name),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
