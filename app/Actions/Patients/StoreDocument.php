<?php

namespace App\Actions\Patients;

use App\Models\Document;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Stores an uploaded patient file on the private clinical disk under a random
 * name. The MIME type comes from the file's content, not from the browser.
 */
class StoreDocument
{
    public function handle(Patient $patient, UploadedFile $file, ?string $category, User $actor): Document
    {
        $disk = (string) config('clinic.storage.clinical_disk');
        $path = $file->store("patients/{$patient->id}", $disk);
        abort_if($path === false, 500, 'The file could not be stored.');

        try {
            return DB::transaction(function () use ($patient, $file, $category, $actor, $disk, $path) {
                $document = $patient->documents()->create([
                    'uploaded_by' => $actor->id,
                    'category' => $category,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                    'mime_type' => (string) $file->getMimeType(),
                    'size_bytes' => (int) $file->getSize(),
                ]);

                Audit::record('clinical.document_uploaded', $document, ['category' => $category]);

                return $document;
            });
        } catch (\Throwable $e) {
            // Do not leave an orphaned file behind if the record could not be saved.
            Storage::disk($disk)->delete($path);
            throw $e;
        }
    }
}
