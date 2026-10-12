<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Writes audit entries. Pass identifiers and short context only. Never pass
 * clinical content (notes, diagnoses, histories) in $metadata.
 */
final class Audit
{
    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public static function record(string $action, ?Model $subject = null, array $metadata = [], ?int $userId = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'patient_id' => self::patientIdFor($subject),
            'ip_address' => Request::ip(),
            'user_agent' => Str::limit((string) Request::userAgent(), 490, ''),
            'metadata' => $metadata ?: null,
        ]);
    }

    private static function patientIdFor(?Model $subject): ?int
    {
        if ($subject instanceof Patient) {
            return $subject->id;
        }

        $patientId = $subject?->getAttribute('patient_id');

        return is_int($patientId) ? $patientId : null;
    }
}
