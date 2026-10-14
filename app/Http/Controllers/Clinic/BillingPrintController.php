<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\Permission;
use App\Enums\TreatmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Printable documents: an invoice, and an estimate of planned treatment.
 * Both open in a new tab with a print layout (no app chrome).
 */
class BillingPrintController extends Controller
{
    public function invoice(Patient $patient, int $invoice): View
    {
        Gate::authorize('view', $patient);
        Gate::authorize(Permission::BillingManage->value);

        // Scoped to the patient in the URL, so another patient's invoice id is a 404.
        $record = $patient->invoices()->with(['lines', 'payments' => fn ($q) => $q->whereNull('voided_at'), 'creator:id,name'])->findOrFail($invoice);
        Audit::record('billing.invoice_printed', $record, ['patient_id' => $patient->id]);

        return view('clinic.billing.invoice', ['patient' => $patient, 'invoice' => $record]);
    }

    public function estimate(Request $request, Patient $patient): View
    {
        Gate::authorize('view', $patient);
        abort_unless($request->user()?->can('viewClinical', $patient) || $request->user()?->can(Permission::BillingManage->value), 403);

        $planId = $request->integer('plan') ?: null;
        $items = $patient->treatmentItems()->with(['procedure', 'surfaces', 'plan'])
            ->whereIn('status', [TreatmentStatus::Planned->value, TreatmentStatus::InProgress->value])
            ->when($planId, fn ($q) => $q->where('treatment_plan_id', $planId))
            ->orderBy('treatment_plan_id')->orderBy('id')->get();
        $plan = $planId ? $patient->treatmentPlans()->findOrFail($planId) : null;

        return view('clinic.billing.estimate', ['patient' => $patient, 'items' => $items, 'plan' => $plan]);
    }
}
