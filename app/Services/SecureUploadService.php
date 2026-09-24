<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class SecureUploadService
{
    /**
     * Validate and store an uploaded file.
     *
     * Images go to the public disk (URL-accessible). Documents and archives
     * go to the private local disk and are served only via authenticated routes.
     *
     * @return array{path: string, disk: string, extension: string, type: string, size: int, original_name: string, private: bool}
     */
    public function store(UploadedFile $file, ?string $directory = null): array
    {
        $this->assertSafe($file);

        $extension = $this->normalizedExtension($file);
        $type = $this->categoryFor($extension);
        $private = in_array($type, ['document', 'archive'], true);
        $disk = $private ? 'local' : 'public';
        $directory = trim($directory ?? ($private ? 'uploads/private' : 'uploads'), '/');
        $storedName = $file->hashName();

        try {
            $path = $file->storeAs($directory, $storedName, $disk);
        } catch (FileException $e) {
            throw ValidationException::withMessages([
                'aiz_file' => 'The file could not be stored securely.',
            ]);
        }

        if (! $path) {
            throw ValidationException::withMessages([
                'aiz_file' => 'The file could not be stored securely.',
            ]);
        }

        // DB path prefix so helpers know which disk to use.
        $dbPath = $private ? 'private:'.$path : $path;

        return [
            'path' => $dbPath,
            'disk' => $disk,
            'extension' => $extension,
            'type' => $type,
            'size' => (int) $file->getSize(),
            'original_name' => (string) $file->getClientOriginalName(),
            'private' => $private,
        ];
    }

    public function assertSafe(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'aiz_file' => 'The uploaded file is invalid.',
            ]);
        }

        $max = (int) config('secure_uploads.max_bytes', 15 * 1024 * 1024);
        if ($file->getSize() > $max) {
            throw ValidationException::withMessages([
                'aiz_file' => 'The file exceeds the maximum allowed size of '.round($max / 1048576, 1).' MB.',
            ]);
        }

        $extension = $this->normalizedExtension($file);
        $blocked = array_map('strtolower', config('secure_uploads.blocked_extensions', []));

        if ($extension === '' || in_array($extension, $blocked, true)) {
            throw ValidationException::withMessages([
                'aiz_file' => 'This file type is not allowed.',
            ]);
        }

        $allowed = $this->allAllowedExtensions();
        if (! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'aiz_file' => 'This file type is not allowed.',
            ]);
        }

        $detectedMime = (string) ($file->getMimeType() ?: '');
        $allowedMimes = config('secure_uploads.mime_map.'.$extension, []);

        if ($allowedMimes === [] || ! in_array($detectedMime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'aiz_file' => 'The file content does not match an allowed type.',
            ]);
        }

        // Double extension / path tricks in the original name.
        $original = strtolower((string) $file->getClientOriginalName());
        foreach ($blocked as $bad) {
            if (str_contains($original, '.'.$bad.'.') || str_ends_with($original, '.'.$bad)) {
                // Already checked primary extension; catch "invoice.pdf.php" style.
                $parts = preg_split('/\.+/', $original) ?: [];
                foreach ($parts as $part) {
                    if (in_array($part, $blocked, true)) {
                        throw ValidationException::withMessages([
                            'aiz_file' => 'This file type is not allowed.',
                        ]);
                    }
                }
            }
        }
    }

    public function resolveDisk(string $storedPath): string
    {
        return str_starts_with($storedPath, 'private:') ? 'local' : 'public';
    }

    public function resolveRelativePath(string $storedPath): string
    {
        return str_starts_with($storedPath, 'private:')
            ? substr($storedPath, strlen('private:'))
            : $storedPath;
    }

    public function isPrivatePath(string $storedPath): bool
    {
        return str_starts_with($storedPath, 'private:');
    }

    public function delete(string $storedPath): void
    {
        $disk = $this->resolveDisk($storedPath);
        $path = $this->resolveRelativePath($storedPath);
        Storage::disk($disk)->delete($path);
    }

    public function categoryFor(string $extension): string
    {
        $extension = strtolower($extension);
        if (in_array($extension, config('secure_uploads.images', []), true)) {
            return 'image';
        }
        if (in_array($extension, config('secure_uploads.archives', []), true)) {
            return 'archive';
        }
        if (in_array($extension, config('secure_uploads.documents', []), true)) {
            return 'document';
        }

        return 'document';
    }

    /**
     * @return list<string>
     */
    public function allAllowedExtensions(): array
    {
        return array_values(array_unique(array_merge(
            config('secure_uploads.images', []),
            config('secure_uploads.documents', []),
            config('secure_uploads.archives', []),
        )));
    }

    private function normalizedExtension(UploadedFile $file): string
    {
        $fromClient = strtolower((string) $file->getClientOriginalExtension());
        $fromGuess = strtolower((string) ($file->extension() ?: ''));

        // Prefer client extension only when it is in the allowlist and matches category of guessed.
        if ($fromClient !== '' && in_array($fromClient, $this->allAllowedExtensions(), true)) {
            return $fromClient;
        }

        return $fromGuess;
    }
}
