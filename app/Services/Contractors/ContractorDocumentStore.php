<?php

namespace App\Services\Contractors;

use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Contractor documents live on the private disk, encrypted at rest. Only Super Admin can open them. */
class ContractorDocumentStore
{
    public const DISK = 'local';

    public const RENEWAL = '.renewal';

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function save(ContractorProfile $profile, string $type, ?UploadedFile $file, array $attributes = []): ContractorDocument
    {
        $current = $profile->documents()->where('type', $type)->first();
        if (! $file && $current && ! $this->differs($current, $attributes)) {
            return $current;
        }
        // A valid approved document keeps the contractor working while its replacement waits for review.
        $targetType = $current?->isValid() ? $type.self::RENEWAL : $type;
        $document = $profile->documents()->firstOrNew(['type' => $targetType]);
        if (! $file && $targetType !== $type && ! $document->exists && ! $this->differs($current, $attributes)) {
            return $current;
        }
        $wasApproved = $current?->status === ContractorDocument::APPROVED;

        if ($file) {
            $path = 'contractor-documents/'.$profile->id.'/'.Str::uuid()->toString().'.bin';
            Storage::disk(self::DISK)->put($path, Crypt::encryptString($file->get()));
            if ($document->path) {
                Storage::disk($document->disk ?: self::DISK)->delete($document->path);
            }
            $document->fill([
                'disk' => self::DISK,
                'path' => $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
                'mime' => $file->getMimeType(),
                'encrypted' => true,
            ]);
        }

        $document->fill($attributes);
        $changed = $file || $document->isDirty(['reference', 'issuer', 'cover_amount', 'expires_at']);
        if (! $document->exists || $changed || $document->status === ContractorDocument::REJECTED) {
            $document->fill([
                'status' => ContractorDocument::PENDING,
                'submitted_at' => now(),
                'verified_at' => null,
                'verified_by' => null,
                'reminder_stage' => null,
                'expired_notified_at' => null,
            ]);
            if ($document->status === ContractorDocument::PENDING && $document->getOriginal('status') === ContractorDocument::REJECTED) {
                $document->rejected_reason = null;
            }
        }
        $document->disk = $document->disk ?: self::DISK;
        $document->save();

        if ($changed) {
            $profile->log($wasApproved ? 'document_renewed' : 'document_uploaded', $profile->user, $type);
        }

        return $document;
    }

    public function renewalFor(ContractorProfile $profile, string $type): ?ContractorDocument
    {
        return $profile->document($type.self::RENEWAL);
    }

    /** Admin approved a renewal: it replaces the live document. */
    public function promote(ContractorDocument $renewal): ContractorDocument
    {
        $type = str_replace(self::RENEWAL, '', $renewal->type);
        $current = ContractorDocument::query()
            ->where('contractor_profile_id', $renewal->contractor_profile_id)
            ->where('type', $type)
            ->first();
        if ($current) {
            if ($current->path && $current->path !== $renewal->path) {
                Storage::disk($current->disk ?: self::DISK)->delete($current->path);
            }
            $current->forceFill($renewal->only([
                'status', 'reference', 'issuer', 'cover_amount', 'disk', 'path', 'original_name', 'mime',
                'encrypted', 'expires_at', 'submitted_at', 'verified_at', 'verified_by', 'rejected_reason',
            ]) + ['reminder_stage' => null, 'expired_notified_at' => null])->save();
            $renewal->delete();

            return $current;
        }
        $renewal->forceFill(['type' => $type])->save();

        return $renewal;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function differs(?ContractorDocument $document, array $attributes): bool
    {
        if (! $document) {
            return $attributes !== [];
        }
        foreach ($attributes as $key => $value) {
            $current = $document->getAttribute($key);
            if ($key === 'expires_at') {
                $current = $current?->format('Y-m-d');
                $value = $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : null;
            } elseif ($key === 'cover_amount') {
                $current = $current === null ? null : (float) $current;
                $value = $value === null || $value === '' ? null : (float) $value;
            }
            if ((string) $current !== (string) $value) {
                return true;
            }
        }

        return false;
    }

    public function download(ContractorDocument $document): Response
    {
        abort_if(blank($document->path), 404);
        $disk = Storage::disk($document->disk ?: self::DISK);
        abort_unless($disk->exists($document->path), 404);
        $contents = $disk->get($document->path);
        if ($document->encrypted) {
            $contents = Crypt::decryptString($contents);
        }
        $name = $document->original_name ?: ($document->type.'.pdf');

        return response($contents, 200, [
            'Content-Type' => $document->mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $name).'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function purge(ContractorDocument $document): void
    {
        if ($document->path) {
            Storage::disk($document->disk ?: self::DISK)->delete($document->path);
        }
        $document->forceFill([
            'path' => null,
            'original_name' => null,
            'status' => ContractorDocument::PURGED,
        ])->save();
    }
}
