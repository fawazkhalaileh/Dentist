<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Patient;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a patient document from the private disk to staff who may see the
 * patient's clinical record. Files are never reachable through a public URL.
 */
class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, Patient $patient, int $document): StreamedResponse
    {
        Gate::authorize('viewClinical', $patient);

        // Scoped to the patient in the URL, so ids of other patients' files 404.
        $file = $patient->documents()->findOrFail($document);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        Audit::record('clinical.document_downloaded', $file);

        $inline = $request->boolean('inline') && $file->isViewableInline();
        $name = str_replace(['"', '\\', "\r", "\n"], '', $file->original_name) ?: 'document';

        return Storage::disk($file->disk)->response($file->path, $name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            // A sandboxed CSP stops any script inside a viewed file from running in the app's origin.
            'Content-Security-Policy' => "sandbox; default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; object-src 'self'",
        ], $inline ? 'inline' : 'attachment');
    }
}
